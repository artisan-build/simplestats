<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'app:generate-sitemap';

    protected $description = 'Generate sitemap.xml with localized public routes';

    /** @var string[] */
    private array $publicPaths = [
        '/',
        '/login',
        '/register',
        '/forgot-password',
        '/imprint',
    ];

    public function handle(): int
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $supported = array_keys(config('locales.supported', []));
        $default = config('locales.default', 'en');

        $sitemap = Sitemap::create();

        foreach ($this->publicPaths as $path) {
            $defaultUrl = $baseUrl.$path;

            $url = Url::create($defaultUrl)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority($path === '/' ? 1.0 : 0.8);

            foreach ($supported as $locale) {
                if ($locale === $default) {
                    $url->addAlternate($defaultUrl, $locale);
                } else {
                    $localizedPath = '/'.$locale.($path === '/' ? '' : $path);
                    $url->addAlternate($baseUrl.$localizedPath, $locale);
                }
            }

            $url->addAlternate($defaultUrl, 'x-default');

            $sitemap->add($url);

            foreach ($supported as $locale) {
                if ($locale === $default) {
                    continue;
                }

                $localizedPath = '/'.$locale.($path === '/' ? '' : $path);
                $localizedUrl = Url::create($baseUrl.$localizedPath)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority($path === '/' ? 0.9 : 0.7);

                foreach ($supported as $altLocale) {
                    if ($altLocale === $default) {
                        $localizedUrl->addAlternate($defaultUrl, $altLocale);
                    } else {
                        $altPath = '/'.$altLocale.($path === '/' ? '' : $path);
                        $localizedUrl->addAlternate($baseUrl.$altPath, $altLocale);
                    }
                }

                $localizedUrl->addAlternate($defaultUrl, 'x-default');

                $sitemap->add($localizedUrl);
            }
        }

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap generated at public/sitemap.xml');

        return self::SUCCESS;
    }
}
