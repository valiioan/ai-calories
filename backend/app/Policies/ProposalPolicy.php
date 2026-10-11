<?php

namespace App\Policies;

use App\Models\Proposal;
use App\Models\User;

class ProposalPolicy
{
    public function view(User $user, Proposal $proposal): bool
    {
        return $proposal->user_id === $user->id;
    }

    public function update(User $user, Proposal $proposal): bool
    {
        return $this->view($user, $proposal);
    }
}
