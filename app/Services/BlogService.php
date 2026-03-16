<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use JCFrane\MdBlog\Post;

readonly class BlogService
{
    public function __construct(
        private CodeHighlightService $highlightService,
    ) {}

    public function resolvePostContent(Post $post): string
    {
        return Cache::get(
            self::highlightPostCacheKey($post),
            $post->html
        );
    }

    public function warmPostHighlightCache(Post $post): void
    {
        Cache::put(
            self::highlightPostCacheKey($post),
            $this->highlightService->highlightCodeBlocks($post->html),
            config('md-blog.cache.ttl')
        );
    }

    private static function highlightPostCacheKey(Post $post): string
    {
        return "blog.highlighted.{$post->slug}.{$post->lastModified}";
    }
}
