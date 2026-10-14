<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DpaController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Dpa', [
            'dpa' => Str::markdown(file_get_contents(resource_path('markdown/dpa.md'))),
        ]);
    }
}
