<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: String,
    active: Boolean,
});

const classes = computed(() => {
    return props.active
        ? 'text-indigo-600 dark:text-indigo-400 bg-gray-100 dark:bg-white/5'
        : 'text-gray-700 dark:text-gray-200';
});
</script>

<template>
    <Link :href="href" class="relative flex flex-col items-center justify-center gap-x-3 rounded-lg px-2 py-2  outline-none transition duration-75 hover:bg-gray-100 focus-visible:bg-gray-100 dark:hover:bg-white/5 dark:focus-visible:bg-white/5" :class="classes">
        <div class="mt-1">
            <slot name="icon" />
        </div>
        <div class="truncate text-xs mt-1">
            <slot />
        </div>
    </Link>
</template>
