<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmPassword;

class CheckPasswordConfirmation
{
    public function __invoke(StatefulGuard $guard, User $user, ?string $passwordInput): void
    {
        if (app('impersonate')->isImpersonating()) {
            return;
        }

        $confirmed = app(ConfirmPassword::class)(
            $guard, $user, $passwordInput
        );

        if (! $confirmed) {
            throw ValidationException::withMessages([
                'password' => __('The password is incorrect.'),
            ]);
        }
    }
}
