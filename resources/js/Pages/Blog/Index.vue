<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Header from "@/Pages/Home/Partials/Header.vue";
import Footer from "@/Components/Footer.vue";
import Gradient from "@/Pages/Home/Partials/Gradient.vue";

defineProps({
    posts: Object,
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
    <Head title="Blog" />

    <Gradient position="fixed" />

    <Header />

    <div class="min-h-screen relative">
        <div class="max-w-7xl mx-auto px-6 py-16">
            <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl">Blog</h1>
            <p class="mt-4 text-lg text-gray-600 dark:text-dark-400">Insights, tutorials, and updates from the SimpleStats team.</p>

            <div v-if="posts.data.length" class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="post in posts.data"
                    :key="post.slug"
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
                            class="h-8 w-8 rounded-full object-cover"
                        />
                        <div class="text-sm">
                            <p class="font-medium text-gray-900 dark:text-white">{{ post.author?.name }}</p>
                            <p class="text-gray-500 dark:text-dark-500">{{ formatDate(post.date) }}</p>
                        </div>
                    </div>
                </Link>
            </div>

            <div v-else class="mt-12 text-center text-gray-500 dark:text-dark-400">
                No blog posts yet.
            </div>

            <!-- Pagination -->
            <div v-if="posts.links && posts.last_page > 1" class="mt-12 flex justify-center gap-2">
                <Link
                    v-for="link in posts.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    class="px-4 py-2 rounded-lg text-sm transition-colors"
                    :class="{
                        'bg-indigo-600 text-white': link.active,
                        'bg-white dark:bg-dark-800 text-gray-700 dark:text-dark-300 hover:bg-gray-100 dark:hover:bg-dark-700 border border-gray-200 dark:border-dark-700': !link.active && link.url,
                        'text-gray-400 dark:text-dark-600 cursor-default': !link.url,
                    }"
                    v-html="link.label"
                    :preserve-scroll="true"
                />
            </div>
        </div>
    </div>

    <Footer />
</template>
