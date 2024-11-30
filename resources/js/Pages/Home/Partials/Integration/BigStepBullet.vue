<script setup>
defineProps({
    content: String,
})
</script>

<template>
    <div class="mt-10 xl:mt-0 flex items-center justify-center rounded-full w-16 h-16 bg-indigo-500 text-4xl font-bold text-white">{{ content }}</div>
</template>
