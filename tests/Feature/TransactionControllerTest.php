<?php

namespace Tests\Feature;

use App\Http\Controllers\Transaction\TransactionController;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Models\Entity\Transaction;
use App\Models\Entity\User;
use App\Services\Transaction\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $transactionService;

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushall();
        $this->transactionService = $this->app->make(TransactionService::class);
    }

    public function test_store_transaction()
    {
        // Create a mock user
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        // Create mock request data
        $requestData = [
            'user_id' => $user->id,
            'amount' => 1000,
        ];

        // Create mock request
        $request = StoreTransactionRequest::create('/api/v1/transactions', 'POST', $requestData);

        // Instantiate the controller with the actual service and request
        $controller = new TransactionController($this->transactionService, $request);

        // Call the controller method
        $response = $controller->store($request);

        // Assert the response
        $this->assertEquals(200, $response->status());
        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'amount' => 1000,
            'status' => 'pending',
        ]);
    }

    public function test_view_transaction_history()
    {
        // Create a mock user
        $user = User::factory()->create(['id' => $this->faker->randomNumber(2, true)]);
        $this->actingAs($user, 'api');

        $status = ['pending', 'completed', 'failed'];
        // Create mock transaction history data
        $transaction = Transaction::factory(10)->create([
            'user_id' => $user->id,
            'amount' => 1000,
            'status' => $status[rand(0, 2)],
        ]);

        // Create mock request
        $request = Request::create('/api/v1/transactions/history/', 'GET');

        // Instantiate the controller with the actual service and request
        $controller = new TransactionController($this->transactionService, $request);

        // Call the controller method
        $response = $controller->history($request);

        // Convert response to array and exclude timestamps for comparison
        $responseData = $response->getData(true)['data'];
        $expectedData = Arr::except($transaction->toArray(), ['created_at', 'updated_at']);

        // Assert the response
        $this->assertEquals(200, $response->status());
        $this->assertCount(10, $responseData);
        $this->assertEquals($expectedData, Arr::except($responseData, ['created_at', 'updated_at']));
    }

    public function tearDown(): void
    {
        parent::tearDown();
        Redis::flushall();
    }
}
