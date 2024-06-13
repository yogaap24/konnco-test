<?php

namespace Tests\Feature;

use App\Models\Entity\Transaction;
use App\Models\Entity\User;
use App\Models\Table\TransactionTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class CacheTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function setUp(): void
    {
        parent::setUp();
        Redis::flushall();
    }

    public function test_transaction_history_cache()
    {
        // Create a user and transactions
        $user = User::factory()->create(['id' => $this->faker->randomNumber(2, true)]);

        $status = ['pending', 'completed', 'failed'];
        Transaction::factory()->count(5)->create(['user_id' => $user->id, 'status' => $status[rand(0, 2)]]);

        // Act as the created user
        $this->actingAs($user, 'api');

        // Simulate the history request
        $response = $this->get('/api/v1/transactions/history');

        // Assert that the response is successful
        $response->assertStatus(200);

        // get Redis Key is the key used to store the transaction history in Redis
        $redisKey = 'transaction_history_' . $user->id;
        $getRedisKey = Redis::keys($redisKey);

        // Assert that the Redis key exists
        $this->assertNotEmpty($getRedisKey);

        // Assert that the Redis key has a TTL of 300 seconds
        $this->assertEquals(60 * 5, Redis::ttl($redisKey));

        // Assert that the Redis key contains the transaction history
        $this->assertNotEmpty(Redis::get($redisKey));

        // Assert that the Redis key contains the correct transaction history
        $this->assertEquals(5, count(json_decode(Redis::get($redisKey))));

        // Get data from Redis and compare
        $dataRedis = json_decode(Redis::get($redisKey));
        $data = Transaction::where('user_id', $user->id)->get();

        $this->assertEquals(count($data), count($dataRedis));
    }

    public function test_transaction_summary()
    {
        // Create multiple users
        $users = User::factory()->count(10)->create();

        // Create transactions for each user
        $status = ['pending', 'completed', 'failed'];
        foreach ($users as $user) {
            Transaction::factory()->count(100)->create([
                'user_id' => $user->id,
                'status' => $status[array_rand($status)]
            ]);
        }

        // Pick one user to act as
        $this->actingAs($users->firstOrFail(), 'api');

        // Simulate the summary request
        $response = $this->get('/api/v1/transactions/summary');

        // Assert that the response is successful
        $response->assertStatus(200);

        // Redis Key used to store the transaction summary in Redis
        $redisKey = 'transaction_summary';
        $getRedisKey = Redis::keys($redisKey);

        // Assert that the Redis key exists
        $this->assertNotEmpty($getRedisKey);

        // Assert that the Redis key has a TTL of 300 seconds
        $this->assertEquals(60 * 5, Redis::ttl($redisKey));

        // Assert that the Redis key contains the transaction summary
        $summaryData = Redis::get($redisKey);
        $this->assertNotEmpty($summaryData, "Redis key $redisKey is empty");

        // Optionally, decode and check the structure of the summary data
        $summaryData = json_decode($summaryData, true);
        $this->assertArrayHasKey('total_transactions', $summaryData);
        $this->assertArrayHasKey('average_amount', $summaryData);
        $this->assertArrayHasKey('highest_transaction', $summaryData);
        $this->assertArrayHasKey('lowest_transaction', $summaryData);
        $this->assertArrayHasKey('longest_name_transaction', $summaryData);
        $this->assertArrayHasKey('status_distribution', $summaryData);

        // Assert that total transactions is correct
        $totalTransactions = Transaction::count();
        $this->assertEquals($totalTransactions, $summaryData['total_transactions']);

        // Assert that average amount is correct
        $averageAmount = Transaction::average('amount');
        $this->assertEquals($averageAmount, $summaryData['average_amount']);

        // Assert that highest transaction is correct
        $highestTransaction = Transaction::orderBy('amount', 'desc')->first();
        $this->assertEquals($highestTransaction->amount, $summaryData['highest_transaction']['amount']);

        // Assert that lowest transaction is correct
        $lowestTransaction = Transaction::orderBy('amount')->first();
        $this->assertEquals($lowestTransaction->amount, $summaryData['lowest_transaction']['amount']);

        // Assert that longest name transaction is correct
        $longestNameTransaction = TransactionTable::with(['user' => function ($query) {
            $query->orderByRaw('LENGTH(name) DESC');
        }])->first();
        $this->assertEquals($longestNameTransaction->user->name, $summaryData['longest_name_transaction']['user_name']);

        // Assert that status distribution is correct
        $statusDistribution = Transaction::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        $this->assertEquals($statusDistribution, $summaryData['status_distribution']);
    }

    // Tear down method to close Mockery
    public function tearDown(): void
    {
        parent::tearDown();
        Redis::flushall();
    }
}
