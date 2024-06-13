<?php

namespace Tests\Unit;

use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Models\Table\UserTable;
use App\Services\Transaction\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    protected $transactionServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transactionServiceMock = Mockery::mock(TransactionService::class);
    }

    public function test_user_can_view_transaction()
    {
        $user = $this->createUser();
        $transaction = $this->createTransaction($user);

        $this->transactionServiceMock->shouldReceive('getById')->once()->with($transaction->id)->andReturn($transaction);

        $result = $this->transactionServiceMock->getById($transaction->id);

        $this->assertEquals($transaction->id, $result->id);
    }

    public function test_user_can_create_transaction()
    {
        $user = $this->createUser();
        $transactionData = $this->getTransactionData($user);

        $this->transactionServiceMock->shouldReceive('create')->once()->with($transactionData)->andReturn(true);

        $result = $this->transactionServiceMock->create($transactionData);

        $this->assertTrue($result);
    }

    public function test_user_can_update_transaction()
    {
        $user = $this->createUser();
        $transaction = $this->createTransaction($user);
        $transactionData = $this->getUpdatedTransactionData();

        $this->transactionServiceMock->shouldReceive('update')->once()->with($transaction->id, $transactionData)->andReturn(true);

        $result = $this->transactionServiceMock->update($transaction->id, $transactionData);

        $this->assertTrue($result);
    }

    public function test_user_can_delete_transaction()
    {
        $user = $this->createUser();
        $transaction = $this->createTransaction($user);

        $this->transactionServiceMock->shouldReceive('delete')->once()->with($transaction->id)->andReturn(true);

        $result = $this->transactionServiceMock->delete($transaction->id);

        $this->assertTrue($result);
    }

    public function test_user_can_process_payment()
    {
        $user = $this->createUser();
        $transaction = $this->createTransaction($user);
        $transactionData = $this->getProcessPaymentData($transaction);

        $this->transactionServiceMock->shouldReceive('processPayment')->once()->with($transactionData)->andReturn(true);

        $result = $this->transactionServiceMock->processPayment($transactionData);

        $this->assertTrue($result);
    }

    public function test_user_can_view_transaction_summary()
    {
        $summaryData = $this->getTransactionSummaryData();

        $this->transactionServiceMock->shouldReceive('getSummary')->once()->andReturn($summaryData);

        $result = $this->transactionServiceMock->getSummary();

        $this->assertEquals($summaryData, $result);
    }

    public function test_user_can_view_transaction_history()
    {
        $user = $this->createUser();
        $transaction = $this->createTransaction($user);
        $filters = $this->getTransactionHistoryFilters();

        $this->transactionServiceMock->shouldReceive('history')->once()->withArgs([$filters, $transaction->id])->andReturn($transaction);

        $result = $this->transactionServiceMock->history($filters, $transaction->id);

        $this->assertEquals($transaction->id, $result->id);
    }

    public function test_transaction_validation_rules()
    {
        $user = $this->createUser();

        $this->assertTrue($this->validateTransactionData($this->getValidTransactionData($user)));
        $this->assertInvalidData(['amount' => ''], 'amount', 'The amount field is required.');
        $this->assertInvalidData(['amount' => 'invalid_amount'], 'amount', 'The amount must be a number.');
    }

    protected function assertInvalidData(array $override, string $field, string $message)
    {
        $user = $this->createUser();
        $data = array_merge($this->getValidTransactionData($user), $override);
        $this->assertFalse($this->validateTransactionData($data));
        $this->assertValidationError($data, $field, $message);
    }

    protected function assertValidationError(array $data, string $field, string $message)
    {
        $validator = validator($data, (new StoreTransactionRequest())->rules());

        $this->assertFalse($validator->passes());

        $errors = $validator->errors()->get($field);

        $this->assertCount(1, $errors);
        $this->assertEquals($message, $errors[0]);
    }

    protected function validateTransactionData(array $data)
    {
        $validator = validator($data, (new StoreTransactionRequest())->rules());
        return $validator->passes();
    }

    protected function getValidTransactionData($user)
    {
        return [
            'user_id' => $user->id,
            'amount' => 100.00,
        ];
    }

    protected function createUser()
    {
        return UserTable::create([
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('passwordunittest'),
        ]);
    }

    protected function createTransaction($user)
    {
        return $user->transactions()->create([
            'amount' => 1000,
            'status' => 'pending',
        ]);
    }

    protected function getTransactionData($user)
    {
        return [
            'user_id' => $user->id,
            'amount' => 1000,
            'status' => 'pending',
        ];
    }

    protected function getUpdatedTransactionData()
    {
        return [
            'amount' => 2000,
        ];
    }

    protected function getProcessPaymentData($transaction)
    {
        return [
            'transaction_id' => $transaction->id,
            'status' => 'success',
        ];
    }

    protected function getTransactionSummaryData()
    {
        return [
            'lowest_transaction' => 1000,
            'longest_name_transaction' => 'Transaction with the longest name',
            'status_distribution' => [
                'pending' => 1,
                'success' => 2,
                'failed' => 3,
            ],
        ];
    }

    protected function getTransactionHistoryFilters()
    {
        return [
            'search_columns' => null,
            'search_key' => null,
            'filter_columns' => null,
            'filter_keys' => null,
            'sort_column' => null,
            'sort_type' => null,
            'entries' => mt_rand(1, 15),
            'page' => mt_rand(1, 10),
        ];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
