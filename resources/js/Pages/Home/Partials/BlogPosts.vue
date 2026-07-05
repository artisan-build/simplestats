<script setup>
import { Link } from '@inertiajs/vue3';
import ArrowRightIcon from "bootstrap-icons/icons/arrow-right.svg?component";
import SectionHeader from "@/Pages/Home/Partials/SectionHeader.vue";
import BlogPostCard from "@/Components/Blog/BlogPostCard.vue";

defineProps({
    posts: Array,
});
</script>

<template>
    <!-- Without posts the section still renders as spacer, so the FAQ keeps its distance to the footer -->
    <section class="flex justify-center pb-32">
        <div v-if="posts?.length" class="max-w-screen-md lg:max-w-screen-xl w-full">
            <SectionHeader id="blog" :eyebrow="$t('Blog')">
                {{ $t('Latest from') }}&nbsp;<span class="text-indigo-500 underline">{{ $t('our blog') }}</span>
            </SectionHeader>

            <div class="px-6 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                <BlogPostCard v-for="post in posts" :key="post.slug" :post="post" />
            </div>

            <div class="mt-10 text-center">
                <Link
                    :href="route('blog.index')"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500"
                >
                    {{ $t('View all posts') }}
                    <ArrowRightIcon class="size-4" />
                </Link>
            </div>
        </div>
    </section>
</template>
