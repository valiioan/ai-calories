<?php

namespace App\Policies;

use App\Models\MealItem;
use App\Models\User;

class MealItemPolicy
{
    public function view(User $user, MealItem $item): bool
    {
        return $item->meal()->where('user_id', $user->id)->exists();
    }

    public function update(User $user, MealItem $item): bool
    {
        return $this->view($user, $item);
    }

    public function delete(User $user, MealItem $item): bool
    {
        return $this->view($user, $item);
    }
}
