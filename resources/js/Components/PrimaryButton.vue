<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button :type="type" class="
        inline-flex items-center px-4 py-2
        border border-transparent rounded-md
        font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest
        bg-indigo-500 dark:bg-gray-200 hover:bg-indigo-600 dark:hover:bg-white focus:bg-indigo-600 dark:focus:bg-white active:bg-indigo-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800
        transition ease-in-out duration-150
        disabled:bg-gray-400
    ">
        <slot />
    </button>
</template>
