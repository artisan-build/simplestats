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

    public function hasWarmHighlightCache(Post $post): bool
    {
        return Cache::has(self::highlightPostCacheKey($post));
    }

    /**
     * Keyed by content hash instead of $post->lastModified: every deploy is a
     * fresh git clone, so file mtimes rotate on all posts while their content
     * stays the same. The hash keeps unchanged posts warm across deploys.
     */
    private static function highlightPostCacheKey(Post $post): string
    {
        return 'blog.highlighted.'.$post->slug.'.'.md5($post->html);
    }
}
