<?php

namespace App\Services\Transaction;

use App\Jobs\ProcessTransaction;
use App\Models\Table\TransactionTable;
use App\Services\AppService;
use App\Services\AppServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class TransactionService extends AppService implements AppServiceInterface
{

    public function __construct(TransactionTable $model)
    {
        parent::__construct($model);
    }

    public function dataTable($filter)
    {
        return TransactionTable::datatable($filter)->paginate($filter->entries ?? 15);
    }

    public function history($filter)
    {
        $query = $this->model::query();
        $id = Auth::user()->id;

        $query = $query->where('user_id', $id);

        $redisKey = 'transaction_history_' . $id;
        $redisExpire = 60 * 5; // 5 minutes

        if (Redis::exists($redisKey)) {
            Log::info('Cache hit: ' . $redisKey);
            $dataRedis = json_decode(Redis::get($redisKey));
            $data = $dataRedis;
            if (count($dataRedis) != count($query->get())) {
                Log::info('Cache mismatch, refreshing: ' . $redisKey);
                $dataRedis = Redis::setex($redisKey, $redisExpire, json_encode($query->get()));
                $data = $query->datatable($filter)->paginate($filter->entries ?? 15);
            }
        } else {
            Log::info('Cache miss, setting new cache: ' . $redisKey);
            $dataStore  = $query->datatable($filter);
            Redis::setex($redisKey, $redisExpire, json_encode($dataStore->get()));
            $data = $dataStore->paginate($filter->entries ?? 15);
        }

        return $data;
    }


    public function getSummary()
    {
        $summaryData = $this->getSummaryDataFromRedis();

        if (!$summaryData) {
            $summaryData = $this->generateSummaryData();
        }

        return $summaryData;
    }

    public function getSummaryDataFromRedis()
    {
        $redisKey = 'transaction_summary';
        if (Redis::exists($redisKey)) {
            return json_decode(Redis::get($redisKey), true);
        }
        return null;
    }

    public function generateSummaryData()
    {
        $totalTransactions = $this->model->count();
        $averageAmount = $this->model->average('amount');
        $highestTransaction = $this->model->orderBy('amount', 'desc')->first();
        $lowestTransaction = $this->model->orderBy('amount')->first();
        $longestNameTransaction = $this->model->with(['user' => function ($query) {
            $query->orderBy(DB::raw('LENGTH(name)'), 'desc');
        }])->first();
        $longestNameTransaction['user_name'] = $longestNameTransaction->user->name;
        $longestNameTransaction = $longestNameTransaction->makeHidden('user');
        $statusDistribution = $this->model->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $summaryData = [
            'total_transactions' => $totalTransactions,
            'average_amount' => $averageAmount,
            'highest_transaction' => $highestTransaction,
            'lowest_transaction' => $lowestTransaction,
            'longest_name_transaction' => $longestNameTransaction,
            'status_distribution' => $statusDistribution,
        ];

        Redis::setex('transaction_summary', 60 * 5, json_encode($summaryData));

        return $summaryData;
    }

    public function getById($id)
    {
        return TransactionTable::findOrFail($id);
    }

    public function create($data)
    {
        DB::beginTransaction();
        try {

            $user_id = Auth::user()->id;
            $row = TransactionTable::create([
                'user_id' => $user_id,
                'amount' => $data['amount'],
                'status' => 'pending'
            ]);

            DB::commit();
            return $row;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function processPayment($data)
    {
        try {
            $transactionId = $data['transaction_id'];
            $status = $data['status'];

            ProcessTransaction::dispatch($transactionId, $status);
            $data = [
                'message' => 'Transaction is being processed',
            ];

            return $data;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function update($id, $data)
    {
        $row = TransactionTable::findOrFail($id);
        $row->update([
            'amount' => $data['amount'],
        ]);
        return $row;
    }

    public function delete($id)
    {
        $row = TransactionTable::findOrFail($id);
        $row->delete();
        return $row;
    }
}
