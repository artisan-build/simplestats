<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Header from "@/Pages/Home/Partials/Header.vue";
import Footer from "@/Components/Footer.vue";
import Gradient from "@/Pages/Home/Partials/Gradient.vue";

defineProps({
    post: Object,
    htmlContent: String,
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
    <Head :title="post.title" />

    <Gradient position="fixed" />

    <Header />

    <article class="min-h-screen relative">
        <div class="max-w-3xl mx-auto px-6 py-16">
            <Link :href="route('blog.index')" class="inline-flex items-center text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 mb-8">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to Blog
            </Link>

            <div class="flex flex-wrap gap-2 mb-4">
                <span
                    v-for="tag in post.tags"
                    :key="tag"
                    class="inline-flex items-center rounded-full bg-indigo-50 dark:bg-indigo-900/30 px-2.5 py-0.5 text-xs font-medium text-indigo-700 dark:text-indigo-300"
                >
                    {{ tag }}
                </span>
            </div>

            <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
                {{ post.title }}
            </h1>

            <div class="mt-6 flex items-center gap-4 border-b border-gray-200 dark:border-dark-700 pb-6">
                <img
                    v-if="post.author?.profile_photo_url"
                    :src="post.author.profile_photo_url"
                    :alt="post.author.name"
                    class="h-10 w-10 rounded-full object-cover"
                />
                <div class="text-sm">
                    <p class="font-medium text-gray-900 dark:text-white">{{ post.author?.name }}</p>
                    <p class="text-gray-500 dark:text-dark-500">{{ formatDate(post.date) }}</p>
                </div>
            </div>

            <div
                class="mt-8 prose dark:prose-invert max-w-none prose-indigo prose-pre:bg-gray-900 prose-pre:rounded-xl"
                v-html="htmlContent"
            />
        </div>
    </article>

    <Footer />
</template>
