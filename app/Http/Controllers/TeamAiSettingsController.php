<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamAiSettingsRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

class TeamAiSettingsController extends Controller
{
    public function update(TeamAiSettingsRequest $request, Team $team): RedirectResponse
    {
        $validated = $request->validated();

        if (empty($validated['ai_provider'])) {
            $team->forceFill(['ai_provider' => null, 'ai_model' => null, 'ai_api_key' => null])->save();
        } else {
            $team->forceFill([
                'ai_provider' => $validated['ai_provider'],
                'ai_model' => $validated['ai_model'] ?? null,
                'ai_api_key' => $validated['ai_api_key'] ?? $team->ai_api_key,
            ])->save();
        }

        return back(303);
    }
}
