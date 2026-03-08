<?php

namespace App\Http\Controllers;

use App\Enums\StatsRangePreset;
use App\Http\Requests\CustomStatsRangePresetRequest;
use App\Models\CustomStatsRangePreset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomStatsRangePresetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->customStatsRangePresets->map->toOption()
        );
    }

    public function store(CustomStatsRangePresetRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->customStatsRangePresets()->count() >= 10) {
            return back()->withErrors([
                'name' => 'You can have a maximum of 10 custom presets.',
            ]);
        }

        $user->customStatsRangePresets()->create($request->validated());

        return back();
    }

    public function update(CustomStatsRangePresetRequest $request, CustomStatsRangePreset $customPreset): RedirectResponse
    {
        if ($customPreset->user_id !== $request->user()->id) {
            abort(403);
        }

        $customPreset->update($request->validated());

        return back();
    }

    public function destroy(Request $request, CustomStatsRangePreset $customPreset): RedirectResponse
    {
        if ($customPreset->user_id !== $request->user()->id) {
            abort(403);
        }

        $customPreset->delete();

        return back();
    }

    public function updateBuiltinVisibility(Request $request): RedirectResponse
    {
        $request->validate([
            'visible' => ['present', 'array'],
            'visible.*' => ['string', Rule::enum(StatsRangePreset::class)],
        ]);

        $allPresets = array_column(StatsRangePreset::cases(), 'value');
        $hiddenPresets = array_values(array_diff($allPresets, $request->visible));

        $request->user()->updateSettings('hiddenPresets', $hiddenPresets);

        return back();
    }
}
