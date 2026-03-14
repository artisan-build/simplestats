<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;
use JCFrane\MdBlog\Facades\MdBlog;

class BlogController extends Controller
{
    public function index(Request $request): Response
    {
        $allPosts = MdBlog::latest();

        $page = $request->integer('page', 1);
        $perPage = 12;
        $paginatedPosts = $allPosts->forPage($page, $perPage)->values();

        $authorIds = $paginatedPosts
            ->pluck('meta.author_id')
            ->filter()
            ->unique()
            ->values();

        $authors = User::query()
            ->whereIn('id', $authorIds)
            ->get(['id', 'name', 'profile_photo_path'])
            ->keyBy('id');

        $postsWithAuthors = $paginatedPosts->map(function ($post) use ($authors) {
            $data = $post->toArray();
            $authorId = $post->meta['author_id'] ?? null;
            $data['author'] = $authorId && $authors->has($authorId)
                ? $authors->get($authorId)
                : null;

            return $data;
        });

        $posts = new LengthAwarePaginator(
            $postsWithAuthors,
            $allPosts->count(),
            $perPage,
            $page,
            ['path' => route('blog.index')]
        );

        return Inertia::render('Blog/Index', [
            'posts' => $posts,
        ]);
    }

    public function show(string $slug): Response
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
            'htmlContent' => $post->html,
        ]);
    }
}
