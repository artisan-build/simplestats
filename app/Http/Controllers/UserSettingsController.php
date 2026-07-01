<?php

namespace App\Http\Controllers;

use App\Enums\DashboardPreset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserSettingsController extends Controller
{
    public function setDashboardPreset(Request $request)
    {
        $request->validate([
            'preset' => ['required', Rule::enum(DashboardPreset::class)],
        ]);

        $request->user()->updateSettings('dashboardPreset', $request->preset);

        return redirect()->back();
    }

    public function completeTour(Request $request): JsonResponse
    {
        $request->user()->updateSettings('tourCompleted', true);

        return response()->json(['status' => 'ok']);
    }

    public function setPropertyLifetime(Request $request): JsonResponse
    {
        $request->validate([
            'project_id' => ['nullable', 'integer'],
            'lifetime' => ['required', 'boolean'],
        ]);

        $request->user()->updateSettings(
            "propertyLifetime.{$request->project_id}",
            $request->boolean('lifetime'),
        );

        return response()->json(['status' => 'ok']);
    }
}
