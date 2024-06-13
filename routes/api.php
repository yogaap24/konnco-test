<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Transaction\TransactionController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'v1'], function () {
    Route::group(['middleware' => 'throttle:api'], function () {
        Route::prefix('auth')->controller(AuthController::class)->group(function () {
            Route::post('/login', 'login')->name('auth.login');
            Route::post('/register', 'register')->name('auth.register');
        });

        Route::group(['middleware' => 'auth:api'], function () {
            Route::prefix('auth')->controller(AuthController::class)->group(function () {
                Route::get('/profile', 'profile')->name('auth.profile');
                Route::post('/logout', 'logout')->name('auth.logout');
            });

            Route::prefix('transactions')->controller(TransactionController::class)->group(function () {
                Route::post('', 'store')->name('transaction.store');
                Route::post('/payment', 'payment')->name('transaction.payment');
                Route::get('/history', 'history')->name('transaction.history');
                Route::get('/summary', 'summary')->name('transaction.summary');
            });
        });
    });
});
