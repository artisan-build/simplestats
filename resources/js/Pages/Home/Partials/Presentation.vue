<script setup>
import {ref} from 'vue';

const demoUrl = 'https://demo.simplestats.io/dashboard';
const iframeReady = ref(false);

const appEnv = import.meta.env.VITE_APP_ENV;

const onIframeLoad = () => {
    setTimeout(() => iframeReady.value = true, 100);
};
</script>

<template>
    <section class="animationElement mt-20 flex justify-center px-4">
        <div class="w-full max-w-screen-xl">
            <!-- Interactive demo (xl screens, non-local only) -->
            <!--
            <div v-if="appEnv !== 'local'" class="hidden xl:block relative" v-show="iframeReady">
                <div class="absolute z-10 -top-6 right-4 flex -translate-y-full animate-pulse items-center gap-2">
                    <svg class="mt-5 w-10 -rotate-[24deg] opacity-60 fill-gray-600 dark:fill-gray-400" viewBox="0 0 219 41" xmlns="http://www.w3.org/2000/svg">
                        <g clip-path="url(#clip0_3_248)"><path d="M21.489 29.4305C36.9333 31.3498 51.3198 33.0559 65.7063 34.9753C66.7641 35.1885 67.6104 36.4681 69.9376 38.3875C63.1675 39.2406 57.8783 40.3069 52.5892 40.5201C38.6259 40.9467 24.8741 40.9467 10.9107 40.9467C9.21821 40.9467 7.5257 41.1599 5.83317 40.7334C0.332466 39.6671 -1.57164 36.0416 1.39028 31.1365C2.87124 28.7906 4.56377 26.658 6.46786 24.7386C13.6611 17.4876 21.0659 10.4499 28.4707 3.41224C29.7401 2.13265 31.6442 1.49285 34.183 0C34.6061 10.8765 23.8162 13.8622 21.489 22.3927C23.3931 21.9662 25.0856 21.7529 26.5666 21.3264C83.6894 5.54486 140.601 7.25099 197.3 22.606C203.224 24.0988 208.936 26.4447 214.649 28.5773C217.61 29.6437 220.149 31.9896 218.457 35.6151C216.976 39.2406 214.014 39.2406 210.629 37.7477C172.759 20.6866 132.561 18.7672 91.9404 19.407C70.7838 19.6203 50.0504 21.9662 29.5285 26.8713C26.9897 27.5111 24.4509 28.3641 21.489 29.4305Z"></path></g><defs><clipPath id="clip0_3_248"><rect width="219" height="41"></rect></clipPath></defs>
                    </svg>
                    <span class="text-gray-600 dark:text-gray-400 text-base italic -rotate-3 underline">interactive demo</span>
                </div>
                <div class="rounded-xl overflow-hidden shadow-2xl border border-gray-300 dark:border-dark-600">
                    <div class="flex items-center justify-between px-4 py-1.5 dark:bg-[#f3f1f1] bg-[#363534] border-b dark:border-gray-200 border-gray-700">
                        <div class="flex gap-2 flex-shrink-0">
                            <span class="size-2.5 rounded-full bg-[#ff5f57]"></span>
                            <span class="size-2.5 rounded-full bg-[#febc2e]"></span>
                            <span class="size-2.5 rounded-full bg-[#28c840]"></span>
                        </div>

                        <a :href="demoUrl" target="_blank" class="flex-shrink-0 size-5 flex items-center justify-center dark:text-gray-500 text-gray-400 hover:dark:text-gray-700 hover:text-gray-300 transition-colors" aria-label="Toggle fullscreen">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                            </svg>
                        </a>
                    </div>

                    <div class="relative w-full bg-white dark:bg-gray-900" style="padding-bottom: 64.45%;">
                        <iframe
                            :src="demoUrl"
                            allowfullscreen
                            style="zoom:0.75;"
                            title="Interactive demo of SimpleStats dashboard"
                            class="demo-iframe absolute top-0 left-0 w-full h-full border-0"
                            @load="onIframeLoad"
                        ></iframe>
                    </div>
                </div>
            </div>
            -->
            <!-- Screenshot: shown on small screens, local env, or while iframe loads -->
            <!--<div :class="{ 'xl:hidden': iframeReady && appEnv !== 'local' }">-->
            <div>
                <picture class="block dark:hidden">
                    <source srcset="images/screenshot.webp" type="image/webp">
                    <img width="1280" height="825" src="images/screenshot.png" alt="Web analytics dashboard for Laravel apps" loading="lazy" fetchpriority="low">
                </picture>
                <picture class="hidden dark:block">
                    <source srcset="images/screenshot_dark.webp" type="image/webp">
                    <img width="1280" height="825" src="images/screenshot_dark.png" alt="Web analytics dashboard for Laravel apps" loading="lazy" fetchpriority="low">
                </picture>
            </div>
        </div>
    </section>
</template>

<style scoped>
.animationElement {
  animation: fadeInUp 0.8s ease forwards;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(2rem);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
