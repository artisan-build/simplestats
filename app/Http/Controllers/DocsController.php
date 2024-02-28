<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class DocsController extends Controller
{
    /**
     * TODO handle the docs by the webserver, so that the files acn be served really statically:
     * maybe use this:
     * location /docs {
     *  alias /path/to/project/docs/.vitepress/dist;
     *  try_files $uri $uri/ /docs/index.html;
     *
     *  location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|otf|eot)$ {
     *      expires max;
     *      log_not_found off;
     *  }
     * }
     *
     * @param $path
     * @return Response
     */
    public function __invoke($path = 'index.html')
    {
        // Pfad zur Datei im VitePress dist Ordner
        $filePath = base_path('docs/.vitepress/dist/' . $path);

        // Standardmäßig auf text/html setzen, falls der Dateityp nicht erkannt wird
        $contentType = 'text/html';

        // Content-Type basierend auf der Dateiendung setzen
        $fileExtension = strtolower(File::extension($filePath));
        switch ($fileExtension) {
            case 'css':
                $contentType = 'text/css';
                break;
            case 'js':
                $contentType = 'application/javascript';
                break;
            case 'woff2':
                $contentType = 'font/woff2';
                break;
            case 'json':
                $contentType = 'application/json';
                break;
            // Füge hier weitere Case-Blöcke für andere Dateitypen hinzu, falls nötig
        }

        if (!File::exists($filePath)) {
            abort(404);
        }

        // File::get() zum Lesen der Datei und Response zurückgeben mit dem gesetzten Content-Type
        return new Response(File::get($filePath), 200, ['Content-Type' => $contentType]);
    }
}
