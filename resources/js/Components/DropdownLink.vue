<script setup>
import {Link} from '@inertiajs/vue3';

defineProps({
    href: String,
    as: String,
});

const classes = "block px-4 py-2 text-sm leading-5 text-gray-700 dark:text-dark-300 hover:bg-gray-100 dark:hover:bg-dark-800 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-800 transition duration-150 ease-in-out";
</script>

<template>
    <div>
        <button v-if="as === 'button'" type="submit" :class="`w-full text-start ${classes}`">
            <slot />
        </button>

        <a v-else-if="as === 'a'" :href="href" :class="classes">
            <slot />
        </a>

        <Link v-else :href="href" :class="classes">
            <slot />
        </Link>
    </div>
</template>
