<script setup>
import {computed} from 'vue';
import {Link} from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        default: '#',
    },
    active: {
        type: Boolean,
        default: false,
    },
    as: {
        type: String,
        default: 'a',
    },
});

const classes = computed(() => {
    return props.active
        ? 'bg-gray-100 dark:bg-dark-700'
        : 'text-gray-700 dark:text-dark-200';
}).value + " relative flex flex-col items-center justify-center gap-x-3 rounded-lg px-2 py-2 outline-none transition duration-75 hover:bg-gray-100 focus-visible:bg-gray-100 dark:hover:bg-dark-700";
</script>

<template>
    <button v-if="as === 'button'" :class="classes">
        <div class="mt-1">
            <slot name="icon"/>
        </div>
        <div class="truncate text-xs mt-1">
            <slot/>
        </div>
    </button>
    <Link v-else :href="href" :class="classes">
        <div class="mt-1">
            <slot name="icon"/>
        </div>
        <div class="truncate text-xs mt-1">
            <slot/>
        </div>
    </Link>
</template>
