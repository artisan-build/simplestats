<?php

use App\Models\User;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->skipUnlessSaas();

    $this->blogPath = storage_path('app/testing/blog');
    File::ensureDirectoryExists($this->blogPath);
    config(['md-blog.path' => $this->blogPath]);
    config(['md-blog.cache.enabled' => false]);
});

afterEach(function () {
    if (! isset($this->blogPath)) {
        return;
    }

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

test('blog index hydrates posts with author data', function () {
    $author = User::factory()->create();
    createMarkdownPost($this->blogPath, 'post-with-author', extra: [
        'author_id' => $author->id,
        'date' => '2026-03-02',
    ]);
    createMarkdownPost($this->blogPath, 'post-without-author', extra: [
        'date' => '2026-03-01',
    ]);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Blog/Index')
            ->has('posts.data', 2)
            ->missing('posts.data.0.author.id')
            ->where('posts.data.0.author.name', $author->name)
            ->where('posts.data.1.author', null)
        );
});

test('landing page shows the latest three blog posts without full content', function () {
    $author = User::factory()->create();
    createMarkdownPost($this->blogPath, 'oldest-post', extra: ['date' => '2026-01-01']);
    createMarkdownPost($this->blogPath, 'middle-post', extra: ['date' => '2026-02-01']);
    createMarkdownPost($this->blogPath, 'newer-post', extra: ['date' => '2026-03-01']);
    createMarkdownPost($this->blogPath, 'newest-post', extra: ['date' => '2026-04-01', 'author_id' => $author->id]);
    createMarkdownPost($this->blogPath, 'draft-post', published: false, extra: ['date' => '2026-05-01']);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Home/LandingPage')
            ->has('latestPosts', 3)
            ->where('latestPosts.0.slug', 'newest-post')
            ->missing('latestPosts.0.author.id')
            ->where('latestPosts.0.author.name', $author->name)
            ->where('latestPosts.1.slug', 'newer-post')
            ->where('latestPosts.2.slug', 'middle-post')
            ->missing('latestPosts.0.html')
            ->missing('latestPosts.0.body')
        );
});

test('landing page latest posts are empty when no posts exist', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Home/LandingPage')
            ->has('latestPosts', 0)
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

test('blog show page uses the post title and excerpt for meta tags', function () {
    createMarkdownPost($this->blogPath, 'meta-post', extra: ['title' => 'Meta Post Title', 'excerpt' => 'Meta post excerpt']);

    $this->get(route('blog.show', 'meta-post'))
        ->assertOk()
        ->assertSee('<meta property="og:title" content="Meta Post Title">', false)
        ->assertSee('<meta property="og:description" content="Meta post excerpt">', false)
        ->assertSee('<meta property="og:type" content="article">', false);
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
