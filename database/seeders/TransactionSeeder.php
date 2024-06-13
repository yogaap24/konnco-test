<?php

namespace Database\Seeders;

use App\Models\Entity\Transaction;
use App\Models\Entity\User;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create 10,000 transactions
        $users = User::all();

        Transaction::factory()->count(10000)->make()->each(function ($transaction) use ($users) {
            $transaction->user_id = $users->random()->id;
            $transaction->save();
        });
    }
}
