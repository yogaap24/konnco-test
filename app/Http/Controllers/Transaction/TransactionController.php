<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Transaction\ProcessPaymentRequest;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Services\Transaction\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends ApiController
{
    protected TransactionService $service;

    /**
     * @param TransactionService $service
     * @param Request $request
     */
    public function __construct(TransactionService $service, Request $request)
    {
        $this->service = $service;
        parent::__construct($request);
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $data = $this->service->dataTable($request);
        return $this->sendSuccess($data, null);
    }


    public function history(Request $request)
    {
        $data = $this->service->history($request);
        return $this->sendSuccess($data, null);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreTransactionRequest $request
     * @return JsonResponse
     */
    public function store(StoreTransactionRequest $request)
    {
        try {
            $data = $this->service->create($request);
            return $this->sendSuccess($data, null);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null);
        }
    }

    public function payment(ProcessPaymentRequest $request)
    {
        try {
            $data = $this->service->processPayment($request);
            return response()->json($data);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null);
        }
    }

    public function summary()
    {
        try {
            $data = $this->service->getSummary();
            return response()->json($data);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), null);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param String $id
     * @return JsonResponse
     */
    public function show(string $id)
    {
        $datum = $this->service->getById($id);
        return $this->sendSuccess($datum, null);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateTransactionRequest $request
     * @param String $id
     * @return JsonResponse
     */
    public function update(UpdateTransactionRequest $request, string $id)
    {
        $datum = $this->service->update($id, $request);
        return $this->sendSuccess($datum, null);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param String $id
     * @return JsonResponse
     */
    public function destroy(string $id)
    {
        $datum = $this->service->delete($id);
        return $this->sendSuccess($datum, null);
    }
}
