<script setup>
import {router} from "@inertiajs/vue3";
import {onMounted, ref} from 'vue';

const appEnv = import.meta.env.VITE_APP_ENV;
const isVisible = ref(false);

onMounted(() => {
    setTimeout(() => {
        isVisible.value = true;
    }, 100);
});

function trackGetStartedClick() {
    window.axios.post(route('tracking.get-started-click')).finally(() => {
        router.visit(route('register'));
    });
}
</script>

<template>
    <section class="animationElement text-center pt-24" :class="{ 'opacity-0 transform translate-y-8': !isVisible }">
        <h1 class="m-auto mb-10 text-4xl font-extrabold tracking-tight leading-none md:text-5xl xl:text-6xl dark:text-white px-6 sm:px-3 max-w-screen-md">
            The missing analytics tool for your&nbsp;<span class="text-indigo-500">Laravel</span> application.
        </h1>
        <div class="relative m-auto mb-6 font-light text-gray-500 lg:mb-8 md:text-lg lg:text-xl dark:text-dark-400 px-6 sm:px-3 max-w-screen-md">
            Server-side, GDPR compliant and 100% accurate analytics tool, that goes beyond simple
            counts of views and visits. Discover in-depth metrics like Registrations, Conversion Rate, campaign ROI and much more in just a few <a href="/docs" target="_blank" class="font-bold text-indigo-500">minutes</a>!
            <a href="/docs/introduction.html#server-side-vs-client-side-tracking" target="_blank" class="hidden xl:block absolute right-0 top-0 -mr-64 -mt-16 " style="transform: rotate(30deg);">
                <img alt="Can't be blocked by ad blockers!" width="250" height="112" class="hover:scale-105 transition" src="images/ad-blockers-badge.png" />
            </a>
        </div>
        <div class="flex flex-col md:flex-row justify-center">
            <div>
                <button v-if="$page.props.canRegister" class="mb-4 inline-flex items-center justify-center px-5 py-3 mx-1.5 text-base font-medium text-center text-white rounded-lg bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 dark:focus:ring-indigo-900" @click="trackGetStartedClick">
                    Get started for free
                    <svg class="w-5 h-5 ml-2 -mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                </button>
            </div>
            <div>
                <a v-if="$page.props.canLogin" href="https://demo.simplestats.io/login" :target="(appEnv === 'demo') ? '_self' : '_blank'" class="mb-4 mx-1.5 inline-flex items-center justify-center px-5 py-3 text-base font-medium text-center text-gray-900 dark:text-white border border-gray-300 rounded-lg bg-white dark:bg-dark-800 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:border-dark-700 dark:hover:bg-dark-700 dark:focus:ring-gray-800">
                    Live demo
                </a>
                <a v-if="$page.props.canRegister" href="https://calendly.com/simplestats/30min" target="_blank" class="mb-4 mx-1.5 inline-flex items-center justify-center px-5 py-3 text-base font-medium text-center text-gray-900 dark:text-white border border-gray-300 rounded-lg bg-white dark:bg-dark-800 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:border-dark-700 dark:hover:bg-dark-700 dark:focus:ring-gray-800">
                    Book a call
                </a>
            </div>
        </div>
    </section>
</template>

<style scoped>
.animationElement {
  transition: opacity 0.8s ease, transform 0.8s ease;
}
</style>
