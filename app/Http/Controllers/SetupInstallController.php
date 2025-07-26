<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
