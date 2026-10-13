<?php

namespace App\Services;

use Spatie\ShikiPhp\Shiki;
use Throwable;

class CodeHighlightService
{
    private const array UNSUPPORTED_LANGUAGES = ['env', 'txt', 'text', 'plain'];

    public function highlightCodeBlocks(string $html): string
    {
        return preg_replace_callback(
            '/<pre><code class="language-(\w+)">(.*?)<\/code><\/pre>/s',
            function (array $matches): string {
                $language = $matches[1];

                if (in_array($language, self::UNSUPPORTED_LANGUAGES, true)) {
                    return $matches[0];
                }

                $code = html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');

                try {
                    return Shiki::highlight(
                        code: trim($code),
                        language: $language,
                        theme: 'github-dark',
                    );
                } catch (Throwable) {
                    return $matches[0];
                }
            },
            $html
        );
    }
}
