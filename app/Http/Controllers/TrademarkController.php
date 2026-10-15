<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TrademarkController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Trademark', [
            'trademark' => Str::markdown(file_get_contents(resource_path('markdown/trademark.md'))),
        ]);
    }
}
