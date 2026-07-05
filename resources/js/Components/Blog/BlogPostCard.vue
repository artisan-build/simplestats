<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    post: Object,
});

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}
</script>

<template>
    <Link
        :href="route('blog.show', post.slug)"
        class="group flex flex-col rounded-2xl bg-white dark:bg-dark-800 shadow-sm border border-gray-200 dark:border-dark-700 overflow-hidden hover:shadow-md transition-shadow"
    >
        <div class="flex-1 p-6">
            <div class="flex flex-wrap gap-2 mb-3">
                <span
                    v-for="tag in post.tags"
                    :key="tag"
                    class="inline-flex items-center rounded-full bg-indigo-50 dark:bg-indigo-900/30 px-2.5 py-0.5 text-xs font-medium text-indigo-700 dark:text-indigo-300"
                >
                    {{ tag }}
                </span>
            </div>

            <h2 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                {{ post.title }}
            </h2>

            <p v-if="post.excerpt" class="mt-2 text-sm text-gray-600 dark:text-dark-400 line-clamp-3">
                {{ post.excerpt }}
            </p>
        </div>

        <div class="px-6 pb-5 flex items-center gap-3">
            <img
                v-if="post.author?.profile_photo_url"
                :src="post.author.profile_photo_url"
                :alt="post.author.name"
                class="size-8 rounded-full object-cover"
            />
            <div class="text-sm">
                <p class="font-medium text-gray-900 dark:text-white">{{ post.author?.name }}</p>
                <p class="text-gray-500 dark:text-dark-500">{{ formatDate(post.date) }}</p>
            </div>
        </div>
    </Link>
</template>
