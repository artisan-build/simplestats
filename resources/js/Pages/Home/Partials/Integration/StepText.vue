<script setup>
defineProps({
    content: String,
})
</script>

<template>
    <div v-html="content" class="self-start font-light text-gray-700 text-xl"></div>
</template>
