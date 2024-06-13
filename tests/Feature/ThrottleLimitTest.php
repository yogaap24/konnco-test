<?php

namespace Tests\Feature;

use App\Models\Entity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Tests\TestCase;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Passport\Passport;
use Mockery;

class ThrottleLimitTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $anotherUser;
    protected $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();

        // Creating users for authentication purposes
        $this->user = User::factory()->create();
        $this->anotherUser = User::factory()->create();

        // Clear rate limiter cache before each test
        RateLimiter::clear('api');
    }

    public function test_throttle_limit_exceeded()
    {
        // Acting as the created user
        Passport::actingAs($this->user, ['api']);

        // Define the API endpoint and the rate limit
        $endpoint = '/api/v1/transactions/history';
        $totalRequest = 10;

        // Send requests up to the rate limit
        for ($i = 0; $i < $totalRequest; $i++) {
            $response = $this->get($endpoint);
            $response->assertStatus(Response::HTTP_OK);
        }

        // The next request should exceed the rate limit and return 429
        $response = $this->get($endpoint);
        $response->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
    }

    public function test_throttle_limit_with_different_users()
    {
        // Acting as the first user
        Passport::actingAs($this->user, ['api']);

        // Define the API endpoint and the rate limit
        $endpoint = '/api/v1/transactions/history';
        $totalRequest = 10;

        // Send requests up to the rate limit for the first user
        for ($i = 0; $i < $totalRequest; $i++) {
            $response = $this->get($endpoint);
            $response->assertStatus(Response::HTTP_OK);
        }

        // The next request for the first user should exceed the rate limit and return 429
        $response = $this->get($endpoint);
        $response->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);

        // Acting as the second user
        $this->actingAs($this->anotherUser, 'api');
        $totalRequest = 10;

        // Send requests up to the rate limit for the second user
        for ($i = 0; $i < $totalRequest; $i++) {
            $response = $this->get($endpoint);
            $response->assertStatus(Response::HTTP_OK);
        }

        // The next request for the second user should exceed the rate limit and return 429
        $response = $this->get($endpoint);
        $response->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
    }

    public function test_throttle_limit_with_different_endpoints()
    {
        // Acting as the created user
        Passport::actingAs($this->user, ['api']);

        // Define the API endpoints, their rate limits, and their HTTP methods
        $endpoints = [
            ['url' => '/api/v1/auth/login', 'method' => 'post', 'data' => ['email' => $this->user->email, 'password' => 'password']],
            ['url' => '/api/v1/transactions/history', 'method' => 'get', 'data' => []],
            ['url' => '/api/v1/transactions/summary', 'method' => 'get', 'data' => []],
        ];

        // Send requests up to the rate limit for each endpoint
        foreach ($endpoints as $endpoint) {
            $totalRequest = 10;
            for ($i = 0; $i < $totalRequest; $i++) {
                $response = $this->{$endpoint['method']}($endpoint['url'], $endpoint['data']);
                $response->assertStatus(Response::HTTP_OK);
            }

            // The next request for each endpoint should exceed the rate limit and return 429
            $response = $this->{$endpoint['method']}($endpoint['url'], $endpoint['data']);
            $response->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
        }

        // The next request for each endpoint should exceed the rate limit and return 429
        foreach ($endpoints as $endpoint) {
            $response = $this->{$endpoint['method']}($endpoint['url'], $endpoint['data']);
            $response->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
