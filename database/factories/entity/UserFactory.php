<?php

namespace Database\Factories\Entity;

use App\Models\Entity\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Entity\User>
 */
class UserFactory extends Factory
{

    protected $model = User::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name'       => $this->faker->name,
            'email'      => $this->faker->unique()->safeEmail,
            'password'   => bcrypt('password'), // Default password
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
