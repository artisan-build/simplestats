<?php

namespace App\Actions\Socialstream;

use Illuminate\Validation\ValidationException;
use JoelButcher\Socialstream\Contracts\ResolvesSocialiteUsers;
use JoelButcher\Socialstream\Socialstream;
use Laravel\Socialite\Contracts\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class ResolveSocialiteUser implements ResolvesSocialiteUsers
{
    /**
     * Resolve the user for a given provider.
     */
    public function resolve(string $provider): User
    {
        try {
            $user = Socialite::driver($provider)->user();
        } catch (InvalidStateException) {
            throw ValidationException::withMessages([
                'socialite' => __('Authentication session expired. Please try again.'),
            ]);
        }

        if (Socialstream::generatesMissingEmails()) {
            $user->email = $user->getEmail() ?? ("{$user->id}@{$provider}".config('app.domain'));
        }

        return $user;
    }
}
