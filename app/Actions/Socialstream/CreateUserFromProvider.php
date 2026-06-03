<?php

namespace App\Actions\Socialstream;

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use JoelButcher\Socialstream\Contracts\CreatesConnectedAccounts;
use JoelButcher\Socialstream\Contracts\CreatesUserFromProvider;
use JoelButcher\Socialstream\Socialstream;
use Laravel\Socialite\Contracts\User as ProviderUserContract;

class CreateUserFromProvider implements CreatesUserFromProvider
{
    /**
     * The creates connected accounts instance.
     */
    public CreatesConnectedAccounts $createsConnectedAccounts;

    /**
     * Create a new action instance.
     */
    public function __construct(CreatesConnectedAccounts $createsConnectedAccounts)
    {
        $this->createsConnectedAccounts = $createsConnectedAccounts;
    }

    /**
     * Create a new user from a social provider user.
     */
    public function create(string $provider, ProviderUserContract $providerUser): User
    {
        $user = app(CreateNewUser::class)->create([
            'name' => $providerUser->getName(),
            'email' => $providerUser->getEmail(),
            'sso' => true,
        ]);

        // Marking the email as verified here lets the package's UserObserver::updated track the
        // registration automatically, within this OAuth callback request, so it carries the full
        // tracking payload (IP, page, UTMs) from the live visitor session. The observer recognises
        // that the user is not signed in yet and does not add a login, so the following Login event
        // remains the single source for the login (avoids the double login from #250).
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        if (Socialstream::hasProviderAvatarsFeature() && $providerUser->getAvatar()) {
            $user->setProfilePhotoFromUrl($providerUser->getAvatar());
        }

        $this->createsConnectedAccounts->create($user, $provider, $providerUser);

        return $user;
    }
}
