<?php

namespace App\Http\Controllers;

use App\Http\DataProviders\PowerUsersDataProvider;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PowerUserController extends Controller
{
    public function index(Request $request, PowerUsersDataProvider $dataProvider): Response|RedirectResponse
    {
        if (! $request->user()->currentTeam->tracksUsage(Project::USAGE_USERS)) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('PowerUsers/Index', $dataProvider->toArray());
    }
}
