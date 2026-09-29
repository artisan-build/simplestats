<?php

use App\Models\Passkey;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Passkey as BasePasskey;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;

function createPasskey(User $user): Passkey
{
    return $user->passkeys()->create([
        'name' => 'MacBook',
        'credential_id' => Str::random(32),
        'credential' => [],
    ]);
}

// The WebAuthn signature check needs a real authenticator, so only the
// cryptographic verification is faked. Request parsing, the session stored
// options and everything after the verification run for real.
function fakePasskeyVerification(Passkey $passkey): void
{
    app()->instance(VerifyPasskey::class, new class($passkey) extends VerifyPasskey
    {
        public function __construct(private Passkey $passkey) {}

        public function __invoke(PublicKeyCredential $credential, PublicKeyCredentialRequestOptions $options, ?PasskeyUser $user = null): BasePasskey
        {
            return $this->passkey;
        }
    });
}

function assertionPayload(): array
{
    $clientData = json_encode(['type' => 'webauthn.get', 'challenge' => 'Y2hhbGxlbmdl', 'origin' => config('app.url')]);
    $authenticatorData = hash('sha256', 'localhost', true).chr(1).pack('N', 1);

    return [
        'credential' => [
            'id' => 'Y3JlZGVudGlhbA',
            'rawId' => 'Y3JlZGVudGlhbA',
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => Base64UrlSafe::encodeUnpadded($clientData),
                'authenticatorData' => Base64UrlSafe::encodeUnpadded($authenticatorData),
                'signature' => Base64UrlSafe::encodeUnpadded('signature'),
            ],
        ],
    ];
}

test('the user handle is hex encoded so iOS Safari does not corrupt it', function () {
    $user = User::factory()->create();

    expect($user->getPasskeyUserHandle())
        ->toHaveLength(64)
        ->toMatch('/^[0-9a-f]+$/');
});

test('guests can fetch passkey login options', function () {
    $this->getJson(route('passkey.login-options'))
        ->assertOk()
        ->assertJsonStructure(['options' => ['challenge']]);
});

test('users can log in with a passkey', function () {
    $user = User::factory()->withPersonalTeam()->create(['last_login_at' => now()->subMonth()]);
    fakePasskeyVerification(createPasskey($user));

    $this->getJson(route('passkey.login-options'))->assertOk();

    $this->postJson(route('passkey.login'), assertionPayload())
        ->assertOk()
        ->assertJsonStructure(['redirect']);

    $this->assertAuthenticatedAs($user);
    expect(CarbonImmutable::parse($user->fresh()->last_login_at)->isToday())->toBeTrue();
});

test('users marked for deletion cannot log in with a passkey but learn why', function () {
    $user = User::factory()->create();
    fakePasskeyVerification(createPasskey($user));
    $user->delete();

    $this->getJson(route('passkey.login-options'))->assertOk();

    $response = $this->postJson(route('passkey.login'), assertionPayload());

    $response->assertUnprocessable();
    expect($response->json('errors.credential.0'))->toContain('deactivated');
    $this->assertGuest();
});

test('a passkey confirms the password', function () {
    $user = User::factory()->withPersonalTeam()->create();
    fakePasskeyVerification(createPasskey($user));

    $this->actingAs($user)->getJson(route('passkey.confirm-options'))->assertOk();

    $this->postJson(route('passkey.confirm'), assertionPayload())->assertOk();

    expect(session('auth.password_confirmed_at'))->not->toBeNull();
});

test('users can delete their own passkey', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $passkey = createPasskey($user);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->deleteJson(route('passkey.destroy', $passkey->uuid))
        ->assertOk();

    expect(Passkey::whereKey($passkey)->exists())->toBeFalse();
});

test('a foreign or numeric passkey reference answers 404', function (Closure $reference) {
    $user = User::factory()->withPersonalTeam()->create();
    $foreignPasskey = createPasskey(User::factory()->create());

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->deleteJson(route('passkey.destroy', $reference($foreignPasskey)))
        ->assertNotFound();

    expect(Passkey::whereKey($foreignPasskey)->exists())->toBeTrue();
})->with([
    'foreign uuid' => [fn (Passkey $passkey) => $passkey->uuid],
    'numeric id' => [fn (Passkey $passkey) => $passkey->id],
]);

test('the profile lists passkeys without internal or credential data', function () {
    $user = User::factory()->withPersonalTeam()->create();
    $passkey = createPasskey($user);

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('canManagePasskeys', true)
            ->where('auth.user.has_passkeys', true)
            ->has('passkeys', 1, fn (Assert $row) => $row
                ->where('uuid', $passkey->uuid)
                ->where('name', 'MacBook')
                ->missing('id')
                ->missing('user_id')
                ->missing('credential')
                ->missing('credential_id')
                ->etc()
            )
        );
});

test('passkey endpoints are rate limited', function () {
    foreach (range(1, 10) as $attempt) {
        $this->getJson(route('passkey.login-options'))->assertOk();
    }

    $this->getJson(route('passkey.login-options'))->assertTooManyRequests();
});
