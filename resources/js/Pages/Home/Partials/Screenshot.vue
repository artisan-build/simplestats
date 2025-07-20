<script setup>
import {onMounted, ref} from 'vue';

const isVisible = ref(false);

onMounted(() => {
    setTimeout(() => {
        isVisible.value = true;
    }, 300); // Slightly delayed after Hero for staggered effect
});
</script>

<template>
    <section class="animationElement mt-20 flex justify-center px-4" :class="{ 'opacity-0 transform translate-y-8': !isVisible }">
        <div class="w-full max-w-screen-xl">
            <img class="block dark:hidden" width="1280" height="825" src="images/screenshot.png" alt="Web analytics dashboard for Laravel apps">
            <img class="hidden dark:block" width="1280" height="825" src="images/screenshot_dark.png" alt="Web analytics dashboard for Laravel apps">
        </div>
    </section>
</template>

<style scoped>
.animationElement {
  transition: opacity 0.8s ease, transform 0.8s ease;
}
</style>
