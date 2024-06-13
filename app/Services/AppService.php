<?php

namespace App\Services;

use App\Models\Table\UserTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AppService
{
    protected $model;

    protected $guard = null;

    protected $debug;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->debug = config('app.debug', false); // check debug status
    }

    public function getUserAuth()
    {
        return auth()->user();
    }

    public function getUserTableAuth()
    {
        return UserTable::find(Auth::id());
    }

    /**
     * Send Response Success
     *
     * @param  array|object $data
     * @param  string|array $message
     * @param  int $statusCode
     * @return  object
     */
    protected function sendSuccess($data = null, $message = null, $statusCode = null)
    {
        return (new ResponseService($data))->success($message, $statusCode);
    }

    /**
     * Send Response Error
     *
     * @param  array|object $data
     * @param  string|array $message
     * @param  int $statusCode
     * @return  object
     */
    protected function sendError($data = null, $message = null, $statusCode = null)
    {
        return (new ResponseService($data))->error($message, $statusCode);
    }
}
