<?php

namespace Database\Seeders;

use App\Models\Entity\User;
use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create 1,000 users
        User::factory()->count(1000)->create();
    }
}
