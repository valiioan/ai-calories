<?php

namespace App\Policies;

use App\Models\ActivityEntry;
use App\Models\User;

class ActivityEntryPolicy
{
    public function view(User $user, ActivityEntry $entry): bool
    {
        return $entry->user_id === $user->id;
    }

    public function update(User $user, ActivityEntry $entry): bool
    {
        return $this->view($user, $entry);
    }

    public function delete(User $user, ActivityEntry $entry): bool
    {
        return $this->view($user, $entry);
    }
}
