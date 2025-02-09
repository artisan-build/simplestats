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
        font-semibold text-xs text-white uppercase tracking-widest
        bg-indigo-500 hover:bg-indigo-600 focus:bg-indigo-600 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2
        transition ease-in-out duration-150
        disabled:bg-gray-400
    ">
        <slot />
    </button>
</template>
