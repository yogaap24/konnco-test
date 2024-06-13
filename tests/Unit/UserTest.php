<?php

namespace Tests\Unit;

use App\Http\Requests\User\StoreUserRequest;
use App\Models\Table\UserTable;
use App\Services\Auth\AuthService;
use App\Services\User\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class UserTest extends TestCase
{
    use WithFaker, RefreshDatabase;

    protected $authServiceMock;
    protected $userServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authServiceMock = Mockery::mock(AuthService::class);
        $this->userServiceMock = Mockery::mock(UserService::class);
    }

    public function test_user_registration()
    {
        $userData = $this->getUserRegistrationData();

        $this->authServiceMock->shouldReceive('register')->once()->with($userData)->andReturn(true);

        $result = $this->authServiceMock->register($userData);

        $this->assertTrue($result);
    }

    public function test_user_login()
    {
        $userData = $this->getUserLoginData();

        $this->authServiceMock->shouldReceive('login')->once()->with($userData)->andReturn(true);

        $result = $this->authServiceMock->login($userData);

        $this->assertTrue($result);
    }

    public function test_user_update_profile()
    {
        $user = $this->createUser();
        $userData = $this->getUserUpdateData();

        $this->userServiceMock->shouldReceive('update')->once()->with($user->id, $userData)->andReturn(true);

        $result = $this->userServiceMock->update($user->id, $userData);

        $this->assertTrue($result);
    }

    public function test_user_can_delete_account()
    {
        $user = $this->createUser();

        $this->userServiceMock->shouldReceive('delete')->once()->with($user->id)->andReturn(true);

        $result = $this->userServiceMock->delete($user->id);

        $this->assertTrue($result);
    }

    public function test_user_can_logout()
    {
        $this->authServiceMock->shouldReceive('logout')->once()->andReturn(true);

        $result = $this->authServiceMock->logout();

        $this->assertTrue($result);
    }

    public function test_user_validation_rules()
    {
        $validData = $this->getUserRegistrationData();

        $this->assertTrue($this->validateUserData($validData));

        $this->assertInvalidData(['name' => ''], 'name', 'The name field is required.');
        $this->assertInvalidData(['email' => 'invalid-email'], 'email', 'The email must be a valid email address.');
        $this->assertInvalidData(['password' => 'pass'], 'password', 'The password must be at least 8 characters.');
    }

    protected function assertInvalidData(array $override, string $field, string $message)
    {
        $data = array_merge($this->getUserRegistrationData(), $override);
        $this->assertFalse($this->validateUserData($data));
        $this->assertValidationError($data, $field, $message);
    }

    protected function assertValidationError(array $data, string $field, string $message)
    {
        $validator = validator($data, (new StoreUserRequest())->rules());

        $this->assertFalse($validator->passes());

        $errors = $validator->errors()->get($field);

        $this->assertCount(1, $errors);
        $this->assertEquals($message, $errors[0]);
    }

    protected function validateUserData(array $data)
    {
        $validator = validator($data, (new StoreUserRequest())->rules());
        return $validator->passes();
    }

    protected function getUserRegistrationData()
    {
        $password = bcrypt('passwordunittest');
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => $password,
            'password_confirmation' => $password,
        ];
    }

    protected function getUserLoginData()
    {
        $password = bcrypt('passwordunittest');
        return [
            'email' => $this->faker->unique()->safeEmail,
            'password' => $password,
        ];
    }

    protected function getUserUpdateData()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
