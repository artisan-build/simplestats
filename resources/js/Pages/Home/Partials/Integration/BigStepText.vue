<script setup>
defineProps({
    content: String,
})
</script>

<template>
    <div class="hidden xl:block text-indigo-500 uppercase font-bold text-4xl">{{ content }}</div>
</template>
