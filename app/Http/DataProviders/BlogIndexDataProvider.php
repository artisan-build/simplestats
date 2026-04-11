<?php

namespace App\Http\DataProviders;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use JCFrane\MdBlog\Facades\MdBlog;

class BlogIndexDataProvider extends AbstractDataProvider
{
    private const PER_PAGE = 12;

    public function __construct(
        private Request $request,
    ) {}

    public function toArray(): array
    {
        $allPosts = MdBlog::latest();

        $page = $this->request->integer('page', 1);
        $paginatedPosts = $allPosts->forPage($page, self::PER_PAGE)->values();

        $authors = $this->loadAuthors($paginatedPosts);
        $postsWithAuthors = $this->hydratePostsWithAuthors($paginatedPosts, $authors);

        return [
            'posts' => $this->buildPaginator($postsWithAuthors, $allPosts->count(), $page),
        ];
    }

    private function loadAuthors(Collection $posts): Collection
    {
        $authorIds = $posts
            ->pluck('meta.author_id')
            ->filter()
            ->unique()
            ->values();

        return User::query()
            ->whereIn('id', $authorIds)
            ->get(['id', 'name', 'profile_photo_path'])
            ->keyBy('id');
    }

    private function hydratePostsWithAuthors(Collection $posts, Collection $authors): Collection
    {
        return $posts->map(function ($post) use ($authors) {
            $data = $post->toArray();
            $authorId = $post->meta['author_id'] ?? null;
            $data['author'] = $authorId && $authors->has($authorId)
                ? $authors->get($authorId)
                : null;

            return $data;
        });
    }

    private function buildPaginator(Collection $postsWithAuthors, int $totalCount, int $page): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            $postsWithAuthors,
            $totalCount,
            self::PER_PAGE,
            $page,
            ['path' => route('blog.index')]
        );
    }
}
