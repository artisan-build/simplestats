<script setup>

const demoUrl = 'https://demo.simplestats.io';

const toggleFullscreen = () => {
    const iframe = document.querySelector('.demo-iframe');
    if (iframe) {
        if (iframe.requestFullscreen) {
            iframe.requestFullscreen();
        } else if (iframe.webkitRequestFullscreen) {
            iframe.webkitRequestFullscreen();
        }
    }
};
</script>

<template>
    <section class="animationElement mt-20 flex justify-center px-4">
        <div class="w-full max-w-screen-xl">
            <!-- Interactive demo with browser chrome (hidden on mobile, shown if demo is available) -->
            <div class="hidden xl:block rounded-xl overflow-hidden shadow-2xl bg-white dark:bg-gray-800">
                <div>
                    <!-- Safari-style address bar -->
                    <div class="flex items-center justify-between px-4 py-1.5 dark:bg-[#f3f1f1] bg-[#363534] border-b dark:border-gray-200 border-gray-700">
                        <!-- Traffic light buttons -->
                        <div class="flex gap-2 flex-shrink-0">
                            <span class="size-2.5 rounded-full bg-[#ff5f57]"></span>
                            <span class="size-2.5 rounded-full bg-[#febc2e]"></span>
                            <span class="size-2.5 rounded-full bg-[#28c840]"></span>
                        </div>

                        <!-- Fullscreen button -->
                        <button @click="toggleFullscreen" class="flex-shrink-0 size-5 flex items-center justify-center dark:text-gray-500 text-gray-400 hover:dark:text-gray-700 hover:text-gray-300 transition-colors" aria-label="Toggle fullscreen">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                            </svg>
                        </button>
                    </div>

                    <!-- Iframe content -->
                    <div class="relative w-full bg-white dark:bg-gray-900" style="padding-bottom: 64.45%;">
                        <iframe
                            style="zoom:0.75;"
                            :src="`${demoUrl}/login`"
                            class="demo-iframe absolute top-0 left-0 w-full h-full border-0"
                            allowfullscreen
                            title="Interactive demo of SimpleStats dashboard"
                        ></iframe>
                    </div>
                </div>
            </div>

            <!-- Fallback static screenshots (visible on mobile or if demo is unavailable) -->
            <div class="xl:hidden">
                <img class="block dark:hidden" width="1280" height="825" src="images/screenshot.png" alt="Web analytics dashboard for Laravel apps">
                <img class="hidden dark:block" width="1280" height="825" src="images/screenshot_dark.png" alt="Web analytics dashboard for Laravel apps">
            </div>
        </div>
    </section>
</template>

<style scoped>
.animationElement {
  transition: opacity 0.8s ease, transform 0.8s ease;
}
</style>
