<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Jetstream\Jetstream;

class ImprintController extends Controller
{
    public function show(Request $request)
    {
        $imprintFile = Jetstream::localizedMarkdownPath('imprint.md');

        return Inertia::render('Imprint', [
            'imprint' => Str::markdown(file_get_contents($imprintFile)),
        ]);
    }
}
