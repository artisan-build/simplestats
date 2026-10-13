<?php

namespace App\Console\Commands;

use App\Services\BlogService;
use Illuminate\Console\Command;
use JCFrane\MdBlog\Facades\MdBlog;
use JCFrane\MdBlog\Post;

class WarmBlogCacheCommand extends Command
{
    protected $signature = 'blog:warm-cache
                            {slugs?* : Warm only the posts with these slugs}
                            {--missing : Also warm posts whose cache entry is missing or expired}';

    /**
     * Shiki highlighting spawns a Node.js subprocess, but Laravel Herd's PHP-FPM
     * environment cannot locate the Node binary (it's not on the restricted PATH).
     * Running this command from the CLI (e.g. in a deploy script) pre-warms the
     * cache so web requests never need to invoke Node themselves. Running from the
     * terminal works because the CLI PHP process has full access to the system PATH,
     * unlike the restricted FPM environment.
     */
    protected $description = 'Pre-warm blog post syntax highlighting cache';

    public function handle(BlogService $blogService): int
    {
        $slugs = collect($this->argument('slugs'));
        $posts = MdBlog::latest();

        $unknown = $slugs->diff($posts->pluck('slug'));
        if ($unknown->isNotEmpty()) {
            $this->warn("Skipping unknown or unpublished slugs: {$unknown->implode(', ')}");
        }

        if ($slugs->isNotEmpty() || $this->option('missing')) {
            $posts = $posts->filter(fn (Post $post): bool => $slugs->contains($post->slug)
                || ($this->option('missing') && ! $blogService->hasWarmHighlightCache($post)));
        }

        foreach ($posts as $post) {
            $blogService->warmPostHighlightCache($post);
        }

        $this->info("Warmed cache for {$posts->count()} blog posts.");

        return self::SUCCESS;
    }
}
