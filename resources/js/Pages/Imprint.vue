<script setup>
import { Head } from '@inertiajs/vue3';
import ApplicationLogo from "@/Components/ApplicationLogo.vue";

defineProps({
    imprint: String,
});
</script>

<template>
    <Head title="Imprint" />

    <div class="font-sans text-gray-900 dark:text-gray-100 antialiased">
        <div class="pt-4 bg-gray-100 dark:bg-gray-900">
            <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">
                <div>
                    <ApplicationLogo />
                </div>

                <div class="w-full sm:max-w-2xl mt-6 p-6 bg-white dark:bg-dark-800 shadow-md overflow-hidden sm:rounded-lg prose dark:prose-invert" v-html="imprint" />
            </div>
        </div>
    </div>
</template>
