<?php

namespace App\Models\Table;

use App\Models\Entity\Transaction;
use App\Models\Table\UserTable;
use Illuminate\Support\Facades\Redis;

class TransactionTable extends Transaction
{
    // Relationship to User
    public function user()
    {
        return $this->belongsTo(UserTable::class);
    }
}
