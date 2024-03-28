<script setup>
import {Link} from "@inertiajs/vue3";
import Features from "@/Pages/Home/Partials/Features.vue";
import Pricing from "@/Pages/Home/Partials/Pricing.vue";
import Screenshot from "@/Pages/Home/Partials/Screenshot.vue";
import Header from "@/Pages/Home/Partials/Header.vue";

defineProps({
    plans: Array,
});
</script>

<template>
    <Header />

    <div class="min-h-screen">
        <section class="text-center pt-24">
            <h1 class="mb-10 text-4xl font-extrabold tracking-tight leading-none md:text-5xl xl:text-6xl dark:text-white">
<!--                V1 A simple and GDPR-compliant<br>statistics tool for <span class="text-indigo-500">Laravel</span>.-->
                The missing statistics tool<br/>for your&nbsp;<span class="text-indigo-500">Laravel</span> app.
<!--                V3 A simple statistics tool for <span class="text-indigo-500">Laravel</span><br>to check the ROI of your campaigns in seconds.-->
            </h1>
            <p class="mb-6 font-light text-gray-500 lg:mb-8 md:text-lg lg:text-xl dark:text-gray-400">
<!--                V1 Get highly detailed user, revenue and campaign statistics in no time.<br>-->
<!--                Start knowing your users and campaigns today!-->
                A lightweight and GDPR-compliant statistics tool for Laravel,<br>
                quickly reveals marketing ROI and user conversion with a very simple setup.
<!--                V3 Quickly reveal marketing ROI and<br>user conversion efficiency with a minimal setup.-->
            </p>
            <Link v-if="$page.props.canRegister" :href="route('register')" class="inline-flex items-center justify-center px-5 py-3 mr-3 text-base font-medium text-center text-white rounded-lg bg-indigo-600 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 dark:focus:ring-indigo-900">
                Get started
                <svg class="w-5 h-5 ml-2 -mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
            </Link>
            <Link v-if="$page.props.canLogin" :href="route('login')" class="inline-flex items-center justify-center px-5 py-3 text-base font-medium text-center text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:text-white dark:border-gray-700 dark:hover:bg-gray-700 dark:focus:ring-gray-800">
                Show Demo
            </Link>
        </section>

        <Screenshot />

        <Features />

        <Pricing :plans="plans" />
    </div>
</template>
