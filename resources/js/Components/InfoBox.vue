<script setup>
import InfoCircleIcon from 'bootstrap-icons/icons/info-circle.svg?component';
</script>

<template>
    <div class="border-l-4 border-indigo-300 bg-indigo-100 text-indigo-800 dark:border-indigo-500 dark:bg-indigo-900/50 dark:text-indigo-200 p-3">
        <div class="font-bold mb-3 uppercase">
            <InfoCircleIcon class="size-6 inline-block mr-2" />

            Info
        </div>
        <div class="text-sm dark:text-indigo-200">
        <slot />
        </div>
    </div>
</template>

<style scoped>

</style>
