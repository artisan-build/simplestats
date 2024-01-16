<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import SectionTitle from "@/Components/SectionTitle.vue";

const props = defineProps({
    href: String,
    active: Boolean,
});

const classes = computed(() => {
    return props.active
        ? 'relative flex items-center justify-center gap-x-3 rounded-lg px-2 py-2 text-indigo-600 dark:text-indigo-400 outline-none transition duration-75 hover:bg-gray-100 focus-visible:bg-gray-100 dark:hover:bg-white/5 dark:focus-visible:bg-white/5 bg-gray-100 dark:bg-white/5'
        : 'relative flex items-center justify-center gap-x-3 rounded-lg px-2 py-2 text-gray-700 dark:text-gray-200 outline-none transition duration-75 hover:bg-gray-100 focus-visible:bg-gray-100 dark:hover:bg-white/5 dark:focus-visible:bg-white/5';
});

const smallAside = true;
</script>

<template>
    <Link :href="href" :class="classes">
        <slot name="icon" />
        <span class="flex-1 truncate font-medium" :class="{'hidden': smallAside}">
            <slot />
        </span>
    </Link>
</template>
