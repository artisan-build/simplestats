<script setup>
import ExclamationTriangleIcon from 'bootstrap-icons/icons/exclamation-triangle.svg?component';
</script>

<template>
    <div class="border-l-4 border-rose-300 bg-rose-100 text-rose-800 dark:border-rose-500 dark:bg-rose-900/50 dark:text-rose-200 p-3">
        <div class="font-bold mb-3 uppercase">
            <ExclamationTriangleIcon class="size-6 inline-block mr-2" />

            Warning
        </div>
        <div class="text-sm dark:text-rose-200">
        <slot />
        </div>
    </div>
</template>

<style scoped>

</style>
