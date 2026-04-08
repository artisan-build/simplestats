<script setup>
defineProps({
    content: String,
})
</script>

<template>
    <div class="mt-10 xl:mt-0 hidden xl:flex items-center justify-center rounded-full size-14 bg-indigo-500 border-indigo-300 border-4 text-3xl font-bold text-white">{{ content }}</div>
</template>
