<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserSetting;

class UserSettingPolicy
{
    public function view(User $user, UserSetting $settings): bool
    {
        return $settings->user_id === $user->id;
    }

    public function update(User $user, UserSetting $settings): bool
    {
        return $this->view($user, $settings);
    }
}
