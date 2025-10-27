<?php

namespace App\Actions\Fortify;

use App\Actions\CreateUserNotification;
use App\Models\Team;
use App\Models\User;
use App\Rules\SignedUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Http\Controllers\TeamInvitationController;
use Laravel\Jetstream\Jetstream;
use Laravel\Jetstream\TeamInvitation;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $invitationRule = (config('app.app_type') === 'default' || app()->runningInConsole()) ? 'nullable' : 'required';

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
            'accept_invitation_link' => [$invitationRule, new SignedUrl()],
        ];

        if (isset($input['sso']) && $input['sso']) {
            unset($rules['password']);
            $input['terms'] = true;
        }

        if (session()->has('invitation')) {
            $input['accept_invitation_link'] = session('invitation.acceptInvitationLink') ?? null;
        }

        Validator::make($input, $rules)->validate();

        $invitation = false;
        $invitationRequest = false;

        // before creating the new user, validate his invitation if one is set...
        if (! empty($input['accept_invitation_link'])) {
            $invitationRequest = Request::create($input['accept_invitation_link']);
            $id = explode('/', $invitationRequest->getPathInfo())[2];
            $invitation = TeamInvitation::findOrFail($id);

            Validator::make(array_merge($input, ['invitation_mail' => $invitation->email]), [
                'email' => ['same:invitation_mail'],
            ])->validate();
        }

        return DB::transaction(function () use ($input, $invitationRequest, $invitation) {
            $createUser = [
                'name' => $input['name'],
                'email' => $input['email'],
                'settings' => $input['settings'] ?? ['placeholder' => 1],
            ];

            if (isset($input['password'])) {
                $createUser['password'] = Hash::make($input['password']);
            }

            return tap(User::create($createUser), function (User $user) use ($invitationRequest, $invitation) {
                // create the users personal team
                $this->createTeam($user);

                if ($invitationRequest && $invitation) {
                    $this->acceptTeamInvitation($invitationRequest, $invitation, $user);
                }
            });
        });
    }

    /**
     * Accepts a team invitation
     */
    protected function acceptTeamInvitation(Request $request, TeamInvitation $invitation, User $user): void
    {
        $teamInvitation = app(TeamInvitationController::class);
        $teamInvitation->accept($request, $invitation->id);

        $invitation->team->projects->each(function ($project) use ($user) {
            app(CreateUserNotification::class)($user, $project);
        });
    }

    /**
     * Create a personal team for the user.
     */
    protected function createTeam(User $user): void
    {
        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0]."'s Team",
            'personal_team' => true,
        ]));
    }
}
