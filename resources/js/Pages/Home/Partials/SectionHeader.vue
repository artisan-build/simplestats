<script setup>
defineProps({
    id: String,
});
</script>
<template>
    <h2 :id="id" class="pt-32 text-center pb-20 text-3xl font-extrabold tracking-tight leading-none md:text-4xl xl:text-5xl dark:text-white">
        <slot />
    </h2>
</template>
