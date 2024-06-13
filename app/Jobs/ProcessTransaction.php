<?php

namespace App\Jobs;

use App\Models\Table\TransactionTable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ProcessTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transactionId;
    protected $status;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($transactionId, $status)
    {
        $this->transactionId = $transactionId;
        $this->status = $status;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::beginTransaction();
        try {
            // Find the transaction by ID
            $transaction = TransactionTable::where('status', 'pending')
                ->where('id', $this->transactionId)
                ->first();

            if ($transaction) {
                // Update the status of the transaction
                $transaction->status = $this->status;
                $transaction->save();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getTransactionId()
    {
        return $this->transactionId;
    }

    public function getStatus()
    {
        return $this->status;
    }
}
