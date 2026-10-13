<?php

namespace App\Services\Ai;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DocsSearchService
{
    private const array EXCLUDED_FILES = ['index.md', 'markdown-examples.md'];

    private const int HEADING_WEIGHT = 5;

    private const int EXCERPT_CONTEXT_LINES = 10;

    /**
     * Keyword search over the markdown sources in docs/ (the same content the
     * public VitePress site is built from).
     *
     * @return array<int, array{page: string, title: string, url: string, excerpt: string}>
     */
    public function search(string $query, int $limit = 3): array
    {
        $terms = collect(preg_split('/[^\pL\pN_]+/u', Str::lower($query)))
            ->filter(fn (string $term): bool => Str::length($term) >= 3)
            ->unique()
            ->values();

        if ($terms->isEmpty()) {
            return [];
        }

        return collect(File::files(base_path('docs')))
            ->filter(fn ($file): bool => $file->getExtension() === 'md' && ! in_array($file->getFilename(), self::EXCLUDED_FILES, true))
            ->map(fn ($file): ?array => $this->scoreFile($file->getPathname(), $terms))
            ->filter()
            ->sortByDesc('score')
            ->take($limit)
            ->map(fn (array $result) => Arr::except($result, 'score'))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, string>  $terms
     * @return array{page: string, title: string, url: string, excerpt: string, score: int}|null
     */
    private function scoreFile(string $path, Collection $terms): ?array
    {
        $lines = preg_split('/\r?\n/', $this->stripFrontmatter(File::get($path)));

        $score = 0;
        $bestLine = 0;
        $bestLineScore = 0;

        foreach ($lines as $index => $line) {
            $lower = Str::lower($line);
            $hits = $terms->sum(fn (string $term): int => substr_count($lower, $term));

            if ($hits === 0) {
                continue;
            }

            $lineScore = $hits * (str_starts_with(ltrim($line), '#') ? self::HEADING_WEIGHT : 1);
            $score += $lineScore;

            if ($lineScore > $bestLineScore) {
                $bestLineScore = $lineScore;
                $bestLine = $index;
            }
        }

        if ($score === 0) {
            return null;
        }

        $slug = basename($path, '.md');

        return [
            'page' => $slug,
            'title' => $this->title($lines, $slug),
            'url' => url('docs/'.$slug),
            'excerpt' => $this->excerpt($lines, $bestLine),
            'score' => $score,
        ];
    }

    private function stripFrontmatter(string $content): string
    {
        return preg_replace('/\A---\r?\n.*?\r?\n---\r?\n/s', '', $content);
    }

    /**
     * @param  list<string>  $lines
     */
    private function title(array $lines, string $slug): string
    {
        foreach ($lines as $line) {
            if (str_starts_with($line, '# ')) {
                return trim(Str::after($line, '# '));
            }
        }

        return Str::headline($slug);
    }

    /**
     * @param  list<string>  $lines
     */
    private function excerpt(array $lines, int $aroundLine): string
    {
        $start = max(0, $aroundLine - self::EXCERPT_CONTEXT_LINES);

        return trim(implode("\n", array_slice($lines, $start, self::EXCERPT_CONTEXT_LINES * 2 + 1)));
    }
}
