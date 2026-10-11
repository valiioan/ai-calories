<?php

namespace App\Policies;

use App\Models\Food;
use App\Models\User;

class FoodPolicy
{
    public function view(User $user, Food $food): bool
    {
        return $user->exists;
    }
}
