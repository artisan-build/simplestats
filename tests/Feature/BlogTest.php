<?php

use App\Models\BlogPost;

test('blog index page returns successful response', function () {
    $this->get(route('blog.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Blog/Index'));
});

test('blog index page displays published posts', function () {
    BlogPost::factory()->count(3)->create();
    BlogPost::factory()->draft()->create();

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Blog/Index')
            ->has('posts.data', 3)
        );
});

test('blog show page returns successful response for published post', function () {
    $post = BlogPost::factory()->create();

    $this->get(route('blog.show', $post->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Blog/Show')
            ->has('post')
            ->has('htmlContent')
        );
});

test('blog show page returns 404 for draft post', function () {
    $post = BlogPost::factory()->draft()->create();

    $this->get(route('blog.show', $post->slug))
        ->assertNotFound();
});

test('blog show page returns 404 for non-existent slug', function () {
    $this->get(route('blog.show', 'non-existent-slug'))
        ->assertNotFound();
});
