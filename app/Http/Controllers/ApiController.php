<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiController extends Controller
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Send Response Success
     *
     * @param  array|object $data
     * @param  string|array $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendSuccess($data = null, $message = null)
    {
        $data = $this->responseWrapper($data)->success($message);

        return response()->json($data);
    }

    /**
     * Send Response Error
     *
     * @param  array|object $data
     * @param  string|array $message
     * @param  int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendError($data = null, $message = null)
    {
        $data = $this->responseWrapper($data)->error($message);

        return response()->json($data);
    }
}