<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $segment = $request->segment(1);
        $supported = array_keys(config('locales.supported', []));
        $default = config('locales.default', 'en');

        if ($request->query('hl') === $default) {
            cookie()->queue('locale', $default, 60 * 24 * 365);

            return redirect()->to($request->getPathInfo());
        }

        if ($segment && $segment !== $default && in_array($segment, $supported)) {
            app()->setLocale($segment);
            cookie()->queue('locale', $segment, 60 * 24 * 365);

            return $next($request);
        }

        $cookieLocale = $request->cookie('locale');

        if ($cookieLocale && $cookieLocale !== $default && in_array($cookieLocale, $supported)) {
            app()->setLocale($cookieLocale);

            if ($request->isMethod('get') && $this->isLocalizablePath($request->getPathInfo())) {
                $path = $request->getPathInfo();

                return redirect()->to('/'.$cookieLocale.($path === '/' ? '' : $path));
            }

            return $next($request);
        }

        return $next($request);
    }

    private function isLocalizablePath(string $path): bool
    {
        $publicPaths = config('locales.public_paths', []);

        foreach ($publicPaths as $publicPath) {
            if ($path === $publicPath || str_starts_with($path, $publicPath.'/')) {
                return true;
            }
        }

        return false;
    }
}
