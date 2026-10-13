<?php

namespace App\Actions\Jetstream;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Jetstream\Contracts\UpdatesTeamNames;
use Spatie\ValidationRules\Rules\Currency;

class UpdateTeam implements UpdatesTeamNames
{
    /**
     * Validate and update the given team's name.
     *
     * @param  array<string, string>  $input
     */
    public function update(User $user, Team $team, array $input): Team
    {
        Gate::forUser($user)->authorize('update', $team);

        Validator::make($input, [
            'name' => ['required', 'string', 'min:1', 'max:30'],
            'currency' => ['required', new Currency],
            'timezone' => ['required', Rule::in(timezone_identifiers_list())],
        ])->validateWithBag('updateTeam');

        $team->forceFill([
            'name' => $input['name'],
            'currency' => $input['currency'],
            'timezone' => $input['timezone'],
        ])->save();

        return $team;
    }
}
