<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use JCFrane\MdBlog\Facades\MdBlog;
use JCFrane\MdBlog\Post;

readonly class BlogService
{
    public function __construct(
        private CodeHighlightService $highlightService,
    ) {}

    public function latestPostCards(int $limit): Collection
    {
        return $this->toPostCards(MdBlog::latest()->take($limit)->values());
    }

    /**
     * Card-shaped post data for list views, without the full body/html payload.
     *
     * @param  Collection<int, Post>  $posts
     * @return Collection<int, array{slug: string, title: string, excerpt: string, tags: array, date: string, author: ?User}>
     */
    public function toPostCards(Collection $posts): Collection
    {
        $authors = $this->loadAuthors($posts);

        return $posts->map(fn (Post $post) => [
            'slug' => $post->slug,
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'tags' => $post->tags,
            'date' => $post->date->toIso8601String(),
            'author' => $authors->get($post->meta['author_id'] ?? null),
        ]);
    }

    private function loadAuthors(Collection $posts): Collection
    {
        $authorIds = $posts
            ->pluck('meta.author_id')
            ->filter()
            ->unique()
            ->values();

        if ($authorIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $authorIds)
            ->get(['id', 'name', 'profile_photo_path'])
            ->keyBy('id');
    }

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
