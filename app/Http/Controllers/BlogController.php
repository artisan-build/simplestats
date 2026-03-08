<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(): Response
    {
        $posts = BlogPost::query()
            ->published()
            ->with('author:id,name,profile_photo_path')
            ->orderByDesc('published_at')
            ->paginate(12);

        return Inertia::render('Blog/Index', [
            'posts' => $posts,
        ]);
    }

    public function show(string $slug): Response
    {
        $post = BlogPost::query()
            ->published()
            ->with('author:id,name,profile_photo_path')
            ->where('slug', $slug)
            ->firstOrFail();

        return Inertia::render('Blog/Show', [
            'post' => $post,
            'htmlContent' => Str::markdown($post->body),
        ]);
    }
}
