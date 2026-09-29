<?php

namespace App\Actions;

use Illuminate\Contracts\Auth\StatefulGuard;
use Laravel\Fortify\Actions\ConfirmPassword;

/**
 * Lets the inline password dialogs (delete account, regenerate token, ...) be
 * confirmed with a passkey instead: a passkey confirmation leaves a short-lived
 * marker in the session that an empty password may redeem exactly once.
 */
class ConfirmPasswordOrPasskey extends ConfirmPassword
{
    public const SESSION_KEY = 'auth.passkey_confirmed_at';

    public const VALID_FOR_SECONDS = 300;

    public function __invoke(StatefulGuard $guard, $user, ?string $password = null)
    {
        if (filled($password)) {
            return parent::__invoke($guard, $user, $password);
        }

        $confirmedAt = session()->pull(self::SESSION_KEY);

        return $confirmedAt !== null && time() - $confirmedAt <= self::VALID_FOR_SECONDS;
    }
}
