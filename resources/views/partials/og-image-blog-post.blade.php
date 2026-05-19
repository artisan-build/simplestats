<x-og-image view="og-image.blog-post" :data="[
    'title' => $page['props']['post']['title'],
    'author' => $page['props']['post']['author']['name'] ?? '',
    'tags' => $page['props']['post']['tags'] ?? [],
    'avatarUrl' => $page['props']['post']['author']['profile_photo_url'] ?? '',
    'appUrl' => str_replace(['https://', 'http://'], '', config('app.url')),
]" />
