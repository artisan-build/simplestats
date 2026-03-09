<div class="flex h-full w-full flex-col bg-gradient-to-br from-indigo-600 to-purple-700 p-16">
    <span class="text-4xl font-semibold uppercase tracking-widest text-white/80">{{ $appUrl }}</span>

    <div class="flex flex-1 items-center">
        <div class="flex-1">
            <h1 class="text-7xl font-bold leading-tight text-white">{{ $title }}</h1>

            @if (!empty($tags))
                <div class="mt-6 flex space-x-3">
                    @foreach (array_slice($tags, 0, 3) as $tag)
                        <span class="rounded-full bg-white/20 px-4 py-1 text-lg text-white">{{ $tag }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($avatarUrl)
            <img src="{{ $avatarUrl }}" alt="{{ $author }}" class="ml-16 h-64 w-64 rounded-full border-4 border-white/30 object-cover">
        @endif
    </div>

    @if ($author)
        <span class="text-2xl text-white/80">{{ $author }}</span>
    @endif
</div>
