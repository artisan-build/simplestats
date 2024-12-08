<?php

namespace App\Observers;

use App\Models\UserBase;

class UserObserver
{
    public function deleted(UserBase $user): void
    {
        if (method_exists($user, 'connectedAccounts')) {
            $user->connectedAccounts()->delete();
        }

        if (method_exists($user, 'customer')) {
            $user->customer()->delete();
        }
    }
}
