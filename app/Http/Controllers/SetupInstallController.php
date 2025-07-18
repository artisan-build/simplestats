<?php

namespace App\Http\Controllers;

use App\Actions\CreateInstance;
use App\Actions\CreateProject;
use App\Actions\UpdateProject;
use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use App\Services\StatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class SetupInstallController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Setup/Installation', [
            'apiToken' => $request->user()->currentTeam->instances()->first()->tokens->first()->token_plain_part,
        ]);
    }
}
