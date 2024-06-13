<?php

namespace Tests\Feature;

use App\Jobs\ProcessTransaction;
use App\Models\Entity\Transaction;
use App\Models\Entity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TransactionProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_process_transaction_jobs()
    {
        // Create 5 transactions with initial status 'pending'
        $users = User::factory()->count(10)->create();
        foreach ($users as $user) {
            $transactions = Transaction::factory()->count(100)->create([
                'user_id' => $user->id,
                'status' => 'pending'
            ]);
        }

        // Dispatch jobs for each transaction with random status 'completed' or 'failed'
        foreach ($transactions as $transaction) {
            $status = $this->getRandomStatus();
            ProcessTransaction::dispatch($transaction->id, $status);
        }

        // Assert that each job was pushed to the queue
        foreach ($transactions as $transaction) {
            Queue::assertPushed(ProcessTransaction::class, function ($job) use ($transaction) {
                return $job->getTransactionId() === $transaction->id;
            });
        }
    }

    private function getRandomStatus()
    {
        $statuses = ['completed', 'failed'];
        return $statuses[array_rand($statuses)];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
