<?php

namespace App\Http\DataProviders;

use App\Services\BlogService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use JCFrane\MdBlog\Facades\MdBlog;

class BlogIndexDataProvider extends AbstractDataProvider
{
    private const int PER_PAGE = 12;

    public function __construct(
        private readonly Request $request,
        private readonly BlogService $blogService,
    ) {}

    public function toArray(): array
    {
        $allPosts = MdBlog::latest();

        $page = $this->request->integer('page', 1);
        $paginatedPosts = $allPosts->forPage($page, self::PER_PAGE)->values();
        $postCards = $this->blogService->toPostCards($paginatedPosts);

        return [
            'posts' => $this->buildPaginator($postCards, $allPosts->count(), $page),
        ];
    }

    private function buildPaginator(Collection $postCards, int $totalCount, int $page): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            $postCards,
            $totalCount,
            self::PER_PAGE,
            $page,
            ['path' => route('blog.index')]
        );
    }
}
