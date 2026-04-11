<?php

namespace App\Http\Controllers;

use App\Http\DataProviders\BlogIndexDataProvider;
use App\Models\User;
use App\Services\BlogService;
use Inertia\Inertia;
use Inertia\Response;
use JCFrane\MdBlog\Facades\MdBlog;

class BlogController extends Controller
{
    public function index(BlogIndexDataProvider $dataProvider): Response
    {
        return Inertia::render('Blog/Index', $dataProvider->toArray());
    }

    public function show(string $slug, BlogService $blogService): Response
    {
        $post = MdBlog::find($slug);

        if (! $post) {
            abort(404);
        }

        $authorId = $post->meta['author_id'] ?? null;
        $author = $authorId
            ? User::query()->find($authorId, ['id', 'name', 'profile_photo_path'])
            : null;

        $postData = $post->toArray();
        $postData['author'] = $author;

        return Inertia::render('Blog/Show', [
            'post' => $postData,
            'htmlContent' => $blogService->resolvePostContent($post),
        ]);
    }
}
