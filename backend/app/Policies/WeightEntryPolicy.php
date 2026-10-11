<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WeightEntry;

class WeightEntryPolicy
{
    public function view(User $user, WeightEntry $entry): bool
    {
        return $entry->user_id === $user->id;
    }

    public function update(User $user, WeightEntry $entry): bool
    {
        return $this->view($user, $entry);
    }

    public function delete(User $user, WeightEntry $entry): bool
    {
        return $this->view($user, $entry);
    }
}
