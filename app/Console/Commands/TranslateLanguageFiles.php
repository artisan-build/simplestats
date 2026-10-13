<?php

namespace App\Console\Commands;

use DeepL\Translator;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TranslateLanguageFiles extends Command
{
    protected $signature = 'app:translate
        {--locale= : Translate only a specific locale (e.g. de)}
        {--force : Re-translate all keys, not just missing ones}
        {--formality=less : DeepL formality level (default, more, less, prefer_more, prefer_less)}
        {--dry-run : Preview changes without making API calls}';

    protected $description = 'Translate language files using DeepL API';

    /** @var array<string, string> */
    private array $deeplLocaleMap = [
        'de' => 'DE',
        'fr' => 'FR',
        'it' => 'IT',
        'es' => 'ES',
        'nl' => 'NL',
    ];

    public function handle(): int
    {
        $authKey = config('services.deepl.auth_key');

        if (! $authKey && ! $this->option('dry-run')) {
            $this->error('DEEPL_AUTH_KEY is not configured. Add it to your .env file.');

            return self::FAILURE;
        }

        $sourcePath = lang_path('en.json');

        if (! File::exists($sourcePath)) {
            $this->error('Source file lang/en.json not found.');

            return self::FAILURE;
        }

        $sourceTranslations = json_decode(File::get($sourcePath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON in lang/en.json: '.json_last_error_msg());

            return self::FAILURE;
        }

        $supported = array_keys(config('locales.supported', []));
        $default = config('locales.default', 'en');
        $targetLocales = collect($supported)->reject(fn ($l): bool => $l === $default);

        if ($this->option('locale')) {
            $targetLocales = $targetLocales->filter(fn ($l): bool => $l === $this->option('locale'));

            if ($targetLocales->isEmpty()) {
                $this->error("Locale '{$this->option('locale')}' is not a supported non-default locale.");

                return self::FAILURE;
            }
        }

        if ($this->option('force')) {
            $this->warn('WARNING: --force will overwrite all existing translations, including manual corrections.');

            if (! $this->confirm('Are you sure you want to re-translate everything?')) {
                $this->info('Aborted.');

                return self::SUCCESS;
            }
        }

        $translator = $this->option('dry-run') ? null : new Translator($authKey);

        foreach ($targetLocales as $locale) {
            $this->translateLocale($locale, $sourceTranslations, $translator);
        }

        $this->info('Done!');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $sourceTranslations
     */
    private function translateLocale(string $locale, array $sourceTranslations, ?Translator $translator): void
    {
        $targetPath = lang_path("{$locale}.json");
        $existing = [];

        if (File::exists($targetPath)) {
            $existing = json_decode(File::get($targetPath), true) ?? [];
        }

        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        $toTranslate = [];
        foreach ($sourceTranslations as $key => $value) {
            if ($force || ! Arr::has($existing, $key)) {
                $toTranslate[$key] = $value;
            }
        }

        $existing = array_intersect_key($existing, $sourceTranslations);

        if (empty($toTranslate)) {
            $this->line("  [{$locale}] No missing keys. Skipping.");

            return;
        }

        $this->line("  [{$locale}] ".count($toTranslate).' key(s) to translate.');

        if ($dryRun) {
            foreach (array_keys($toTranslate) as $key) {
                $this->line("    - {$key}");
            }

            return;
        }

        $deeplLocale = $this->deeplLocaleMap[$locale] ?? Str::upper($locale);
        $values = array_values($toTranslate);

        $formality = $this->option('formality');

        $translated = [];
        foreach (array_chunk($values, 50) as $chunk) {
            $results = $translator->translateText($chunk, 'en', $deeplLocale, ['formality' => $formality]);
            foreach ($results as $result) {
                $translated[] = $result->text;
            }
        }

        $keys = array_keys($toTranslate);
        $newTranslations = array_combine($keys, $translated);

        $merged = [];
        foreach ($sourceTranslations as $key => $value) {
            $merged[$key] = $newTranslations[$key] ?? $existing[$key] ?? $value;
        }

        File::put(
            $targetPath,
            json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n"
        );

        $this->info("  [{$locale}] Translated ".count($toTranslate).' key(s).');
    }
}
