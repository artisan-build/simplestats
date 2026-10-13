<?php

namespace App\Http\Controllers;

use App\Enums\DashboardPreset;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserSettingsController extends Controller
{
    public function setDashboardPreset(Request $request): RedirectResponse
    {
        $request->validate([
            'preset' => ['required', Rule::enum(DashboardPreset::class)],
        ]);

        $request->user()->updateSettings('dashboardPreset', $request->preset);

        return back();
    }

    public function completeTour(Request $request): JsonResponse
    {
        $request->user()->updateSettings('tourCompleted', true);

        return response()->json(['status' => 'ok']);
    }

    public function setLoginSummary(Request $request): RedirectResponse
    {
        $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $request->user()->updateSettings('loginSummaryEnabled', $request->boolean('enabled'));

        return back();
    }

    public function setPropertyLifetime(Request $request): JsonResponse
    {
        $request->validate([
            // The frontend sends the project's public uuid (or 0 for all
            // projects); the value is only used as a settings key.
            'project_id' => ['nullable', function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_numeric($value) && ! Str::isUuid($value)) {
                    $fail('Invalid project.');
                }
            }],
            'lifetime' => ['required', 'boolean'],
        ]);

        $request->user()->updateSettings(
            "propertyLifetime.{$request->project_id}",
            $request->boolean('lifetime'),
        );

        return response()->json(['status' => 'ok']);
    }
}
