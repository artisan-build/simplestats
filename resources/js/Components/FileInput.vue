<script setup>
import {ref} from 'vue';

const input = ref(null);
</script>

<template>
    <input
        type="file"
        ref="input"
        class="block w-full px-3 py-2 text-sm text-gray-600 bg-white border border-gray-200 rounded-md file:bg-gray-200 file:text-gray-700 file:text-sm file:px-4 file:py-1 file:border-none file:rounded-full dark:file:bg-gray-800 dark:file:text-gray-200 dark:text-gray-300 placeholder-gray-400/70 dark:placeholder-gray-500 focus:border-blue-400 focus:outline-none focus:ring focus:ring-blue-300 focus:ring-opacity-40 dark:border-gray-600 dark:bg-dark-800 dark:focus:border-blue-300"
    >
</template>
