<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class DocsController extends Controller
{
    public function __invoke($path = 'index.html')
    {
        $filePath = base_path('docs/.vitepress/dist/' . $path);
        $fileExtension = strtolower(File::extension($filePath));

        $contentType = match($fileExtension) {
            'css' => 'text/css',
            'js' => 'application/javascript',
            'woff2' => 'font/woff2',
            'json' => 'application/json',
            default => 'text/html',
        };

        if (!File::exists($filePath)) {
            abort(404);
        }

        return new Response(File::get($filePath), 200, ['Content-Type' => $contentType]);
    }
}
