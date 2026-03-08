<?php

namespace App\Http\Controllers;

use App\Enums\StatsRangePreset;
use App\Http\Requests\CustomStatsRangePresetRequest;
use App\Models\CustomStatsRangePreset;
use Illuminate\Http\JsonResponse;
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

    public function store(CustomStatsRangePresetRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->customStatsRangePresets()->count() >= 10) {
            return response()->json([
                'message' => 'You can have a maximum of 10 custom presets.',
            ], 422);
        }

        $preset = $user->customStatsRangePresets()->create($request->validated());

        return response()->json($preset->toOption(), 201);
    }

    public function update(CustomStatsRangePresetRequest $request, CustomStatsRangePreset $customPreset): JsonResponse
    {
        if ($customPreset->user_id !== $request->user()->id) {
            abort(403);
        }

        $customPreset->update($request->validated());

        return response()->json($customPreset->toOption());
    }

    public function destroy(Request $request, CustomStatsRangePreset $customPreset): JsonResponse
    {
        if ($customPreset->user_id !== $request->user()->id) {
            abort(403);
        }

        $customPreset->delete();

        return response()->json(null, 204);
    }

    public function toggleBuiltin(Request $request): JsonResponse
    {
        $request->validate([
            'preset' => ['required', 'string', Rule::enum(StatsRangePreset::class)],
        ]);

        $user = $request->user();
        $hiddenPresets = $user->settings['hiddenPresets'] ?? [];

        if (in_array($request->preset, $hiddenPresets)) {
            $hiddenPresets = array_values(array_diff($hiddenPresets, [$request->preset]));
        } else {
            $hiddenPresets[] = $request->preset;
        }

        $user->updateSettings('hiddenPresets', $hiddenPresets);

        return response()->json(['hiddenPresets' => $hiddenPresets]);
    }
}
