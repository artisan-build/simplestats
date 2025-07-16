<script setup>
defineProps({
    content: String,
})
</script>

<template>
    <div v-html="content" class="self-start font-light text-gray-700 dark:text-dark-300 text-xl"></div>
</template>
