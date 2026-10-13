<?php

use App\Services\BlogService;
use App\Services\CodeHighlightService;
use Illuminate\Support\Facades\File;
use JCFrane\MdBlog\Facades\MdBlog;

beforeEach(function (): void {
    $this->skipUnlessSaas();

    $this->blogPath = storage_path('app/testing/blog-warm-cache');
    File::ensureDirectoryExists($this->blogPath);
    config(['md-blog.path' => $this->blogPath]);
    config(['md-blog.cache.enabled' => false]);

    $this->mock(CodeHighlightService::class)
        ->shouldReceive('highlightCodeBlocks')
        ->andReturnUsing(fn (string $html): string => "<!--highlighted-->{$html}");
});

afterEach(function (): void {
    if (! isset($this->blogPath)) {
        return;
    }

    File::deleteDirectory($this->blogPath);
});

function createWarmablePost(string $path, string $slug, string $content = 'Some content.'): void
{
    file_put_contents(
        "{$path}/{$slug}.md",
        "---\ntitle: \"{$slug}\"\nslug: \"{$slug}\"\ndate: \"2026-03-01\"\npublished: true\n---\n\n{$content}\n"
    );
}

test('warms all posts when called without arguments', function (): void {
    createWarmablePost($this->blogPath, 'post-a');
    createWarmablePost($this->blogPath, 'post-b');

    $this->artisan('blog:warm-cache')
        ->expectsOutputToContain('Warmed cache for 2 blog posts.')
        ->assertSuccessful();

    $blogService = resolve(BlogService::class);

    foreach (MdBlog::latest() as $post) {
        expect($blogService->hasWarmHighlightCache($post))->toBeTrue()
            ->and($blogService->resolvePostContent($post))->toStartWith('<!--highlighted-->');
    }
});

test('warms only the given slugs', function (): void {
    createWarmablePost($this->blogPath, 'post-a');
    createWarmablePost($this->blogPath, 'post-b');

    $this->artisan('blog:warm-cache', ['slugs' => ['post-a']])
        ->expectsOutputToContain('Warmed cache for 1 blog posts.')
        ->assertSuccessful();

    $blogService = resolve(BlogService::class);

    expect($blogService->hasWarmHighlightCache(MdBlog::find('post-a')))->toBeTrue()
        ->and($blogService->hasWarmHighlightCache(MdBlog::find('post-b')))->toBeFalse();
});

test('warns about unknown or unpublished slugs', function (): void {
    createWarmablePost($this->blogPath, 'post-a');

    $this->artisan('blog:warm-cache', ['slugs' => ['does-not-exist']])
        ->expectsOutputToContain('Skipping unknown or unpublished slugs: does-not-exist')
        ->expectsOutputToContain('Warmed cache for 0 blog posts.')
        ->assertSuccessful();
});

test('the missing option warms only posts with a cold cache', function (): void {
    createWarmablePost($this->blogPath, 'post-a');
    createWarmablePost($this->blogPath, 'post-b');

    $this->artisan('blog:warm-cache', ['slugs' => ['post-a']]);

    $this->artisan('blog:warm-cache', ['--missing' => true])
        ->expectsOutputToContain('Warmed cache for 1 blog posts.')
        ->assertSuccessful();

    expect(resolve(BlogService::class)->hasWarmHighlightCache(MdBlog::find('post-b')))->toBeTrue();
});

test('cache stays warm when only the file mtime changes, like after a fresh deploy clone', function (): void {
    createWarmablePost($this->blogPath, 'post-a');
    $this->artisan('blog:warm-cache');

    touch("{$this->blogPath}/post-a.md", time() + 60);

    expect(resolve(BlogService::class)->hasWarmHighlightCache(MdBlog::find('post-a')))->toBeTrue();
});

test('a content change makes the cache entry cold', function (): void {
    createWarmablePost($this->blogPath, 'post-a');
    $this->artisan('blog:warm-cache');

    createWarmablePost($this->blogPath, 'post-a', 'Updated content.');

    expect(resolve(BlogService::class)->hasWarmHighlightCache(MdBlog::find('post-a')))->toBeFalse();
});
