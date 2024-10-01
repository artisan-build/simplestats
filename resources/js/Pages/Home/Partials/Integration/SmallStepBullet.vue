<script setup>
defineProps({
    content: String,
})
</script>

<template>
    <div class="hidden xl:block rounded-full w-6 h-6 border-4 bg-white border-indigo-400 m-auto">{{ content }}</div>
</template>
