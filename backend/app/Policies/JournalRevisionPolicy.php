<?php

namespace App\Policies;

use App\Models\JournalRevision;
use App\Models\User;

class JournalRevisionPolicy
{
    public function view(User $user, JournalRevision $revision): bool
    {
        return $revision->user_id === $user->id;
    }

    public function update(User $user, JournalRevision $revision): bool
    {
        return $this->view($user, $revision);
    }
}
