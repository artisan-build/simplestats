<script setup>

</script>

<template>
    <div class="border-l-4 border-indigo-300 bg-indigo-100 text-indigo-800 dark:border-indigo-500 dark:bg-indigo-900/50 dark:text-indigo-200 p-3">
        <div class="font-bold mb-3 uppercase">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 inline-block mr-2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
            </svg>

            Info
        </div>
        <div class="text-sm dark:text-indigo-200">
        <slot />
        </div>
    </div>
</template>

<style scoped>

</style>
