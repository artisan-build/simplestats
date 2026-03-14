<?php

use App\Models\User;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->blogPath = storage_path('app/testing/blog');
    File::ensureDirectoryExists($this->blogPath);
    config(['md-blog.path' => $this->blogPath]);
    config(['md-blog.cache.enabled' => false]);
});

afterEach(function () {
    File::deleteDirectory($this->blogPath);
});

function createMarkdownPost(string $path, string $slug, bool $published = true, array $extra = []): void
{
    $title = $extra['title'] ?? ucfirst(str_replace('-', ' ', $slug));
    $date = $extra['date'] ?? '2026-03-01';
    $excerpt = $extra['excerpt'] ?? 'Test excerpt';
    $authorId = $extra['author_id'] ?? null;

    $frontMatter = "---\ntitle: \"{$title}\"\nslug: \"{$slug}\"\ndate: \"{$date}\"\nexcerpt: \"{$excerpt}\"\npublished: ".($published ? 'true' : 'false')."\ntags:\n  - Testing\n";

    if ($authorId) {
        $frontMatter .= "author_id: {$authorId}\n";
    }

    $frontMatter .= "---\n\nThis is test content for **{$slug}**.";

    file_put_contents("{$path}/{$slug}.md", $frontMatter);
}

test('blog index page returns successful response', function () {
    $this->get(route('blog.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Blog/Index'));
});

test('blog index page displays published posts', function () {
    createMarkdownPost($this->blogPath, 'published-post-one');
    createMarkdownPost($this->blogPath, 'published-post-two');
    createMarkdownPost($this->blogPath, 'published-post-three');
    createMarkdownPost($this->blogPath, 'draft-post', published: false);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Blog/Index')
            ->has('posts.data', 3)
        );
});

test('blog show page returns successful response for published post', function () {
    $author = User::factory()->create();
    createMarkdownPost($this->blogPath, 'test-published-post', extra: ['author_id' => $author->id]);

    $this->get(route('blog.show', 'test-published-post'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Blog/Show')
            ->has('post')
            ->has('htmlContent')
        );
});

test('blog show page returns 404 for draft post', function () {
    createMarkdownPost($this->blogPath, 'draft-post', published: false);

    $this->get(route('blog.show', 'draft-post'))
        ->assertNotFound();
});

test('blog show page returns 404 for non-existent slug', function () {
    $this->get(route('blog.show', 'non-existent-slug'))
        ->assertNotFound();
});
