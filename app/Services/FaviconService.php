<?php

namespace App\Services;

use App\Models\StatsTrackReferer;
use AshAllenDesign\FaviconFetcher\Facades\Favicon;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class FaviconService
{
    private PendingRequest $httpClient;

    public function __construct()
    {
        // some websites need these headers (for instance ecosia.org)
        $this->httpClient = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9',
        ]);
    }

    /**
     * @param  string  $domain domain without http or www prefixed...
     */
    public function download(string $domain): bool
    {
        $basePath = public_path("icons");
        $refererPath = $basePath . "/track_referer";

        if (! is_dir($refererPath)) {
            mkdir($refererPath, 0775, true);
        }

        $filePath = $refererPath . "/$domain.ico";

        // TODO evtl. last modify prüfen und monatlich favicon neu fetchen?
        if (file_exists($filePath)) {
            return true;
        }

        if (! $this->tryDirectly("https://$domain/favicon.ico", $filePath)) {
            if (! $this->tryDirectly("https://www.$domain/favicon.ico", $filePath)) {
                if (! $this->tryWithFetcher("https://$domain", $filePath)) {
                    if (! $this->tryWithFetcher("https://wwww.$domain", $filePath)) {
                        //fallback
                        file_put_contents($filePath, file_get_contents("$basePath/track_referer_default.png"));
                    }
                }
            }
        }

        return true;
    }

    private function tryDirectly (string $faviconUrl, string $filePath): bool
    {
        return $this->try2GetAndStoreFavicon($faviconUrl, $filePath);
    }

    private function tryWithFetcher(string $domainUrl, string $filePath): bool
    {
        $faviconUrl = '';

        try {
            if ($favicon = Favicon::fetch($domainUrl)) {
                $faviconUrl = $favicon->getFaviconUrl();
            }
        } catch (Exception $e) {}

        return $this->try2GetAndStoreFavicon($faviconUrl, $filePath);
    }

    private function try2GetAndStoreFavicon(string $faviconUrl, string $filePath): bool
    {
        try {
            $faviconResponse = $this->httpClient->get($faviconUrl);
        } catch (Exception $e) {}

        if (isset($faviconResponse) && $faviconResponse->successful()) {
            $contentType = $faviconResponse->header('Content-Type');
            $body = $faviconResponse->body();

            // Check if the Content-Type is an image
            if (strpos($contentType, 'image') !== false && strpos($contentType, 'svg') === false && strlen($body) > 0) {
                file_put_contents($filePath, $body);
                return true;
            }
        }

        return false;
    }
}
