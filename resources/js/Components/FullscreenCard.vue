<script setup lang="ts">
import {ArrowLeftIcon} from '@heroicons/vue/24/solid';
import {ArrowLeftEndOnRectangleIcon} from '@heroicons/vue/24/outline';
import {router} from '@inertiajs/vue3';
import AppLayout from "@/Layouts/AppLayout.vue";
import Gradient from "@/Pages/Home/Partials/Gradient.vue";

defineProps({
    title: {
        type: String,
        default: '',
    },
    description: {
        type: String,
        default: '',
    },
});

const goBack = () => window.history.back();
const logout = () => router.post(route('logout'));
</script>

<template>
    <Gradient position="fixed" />
    <AppLayout :title="title">
        <div class="fixed top-4 left-4 z-50 flex gap-2">
            <button @click="goBack" class="p-2 rounded-full dark:bg-dark-600/50 dark:hover:bg-dark-500/50">
                <ArrowLeftIcon class="size-5"/>
            </button>
            <button @click="logout" class="p-2 rounded-full dark:bg-dark-600/50 dark:hover:bg-dark-500/50">
                <ArrowLeftEndOnRectangleIcon class="size-5"/>
            </button>
        </div>
        <!-- -my-4 because of AppLayout->main->p-4 -->
        <div class="w-full min-h-screen flex items-center -my-4">
            <div class="mx-auto w-full sm:max-w-lg p-1.5 rounded-lg bg-gray-200/50 dark:bg-dark-600/50 fade-in-bottom">
            <div class="relative p-10 border rounded-md bg-white dark:bg-dark-800">
                    <h2 v-if="title" class="text-xl font-semibold">{{ title }}</h2>
                    <p v-if="description" class="mb-6">{{ description }}</p>
                    <slot/>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@keyframes fadeInBottom {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in-bottom {
    animation: fadeInBottom 0.6s ease-out;
}
</style>
