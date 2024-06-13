<?php

namespace App\Models\Table;

use App\Models\Entity\User;

class UserTable extends User
{
    public function transactions()
    {
        return $this->hasMany(TransactionTable::class, 'user_id');
    }
}
