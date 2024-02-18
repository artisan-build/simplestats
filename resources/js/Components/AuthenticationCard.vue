<script setup>
import Header from "@/Pages/Home/Partials/Header.vue";
</script>

<template>
    <Header />

    <div class="min-h-screen flex flex-col sm:py-32 items-center bg-white dark:bg-gray-900">
        <div>
            <slot name="logo" />
        </div>

        <div class="w-full sm:max-w-lg mt-6 px-12 py-10 bg-white dark:bg-gray-800 shadow-xl overflow-hidden rounded-lg border">
            <h2 class="text-2xl font-medium text-center mb-6">
                <slot name="title" />
            </h2>
            <slot />
        </div>
    </div>
</template>
