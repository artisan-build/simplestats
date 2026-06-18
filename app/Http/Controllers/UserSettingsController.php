<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSettingsController extends Controller
{
    public function toggleMenu(Request $request)
    {
        $showMenu = $request->user()->settings['showMenu'] ?? true;
        $request->user()->updateSettings('showMenu', ! $showMenu);

        return redirect()->back();
    }

    public function toggleAdvanced(Request $request)
    {
        $showAdvanced = $request->user()->settings['showAdvanced'] ?? false;
        $request->user()->updateSettings('showAdvanced', ! $showAdvanced);

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
