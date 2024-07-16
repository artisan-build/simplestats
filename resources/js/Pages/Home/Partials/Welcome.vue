<script setup>
import {Link} from "@inertiajs/vue3";
import Features from "@/Pages/Home/Partials/Features.vue";
import Pricing from "@/Pages/Home/Partials/Pricing.vue";
import Screenshot from "@/Pages/Home/Partials/Screenshot.vue";
import Header from "@/Pages/Home/Partials/Header.vue";
import SelfHosted from "@/Pages/Home/Partials/SelfHosted.vue";
import Testimonials from "@/Pages/Home/Partials/Testimonials.vue";

defineProps({
    plans: Array,
});

const appEnv = import.meta.env.VITE_APP_ENV;
</script>

<template>
    <Header />

    <div class="min-h-screen bg-white">
        <section class="text-center pt-24">
            <h1 class="mb-10 text-4xl font-extrabold tracking-tight leading-none md:text-5xl xl:text-6xl dark:text-white">
<!--                V1 A simple and GDPR-compliant<br>statistics tool for <span class="text-indigo-500">Laravel</span>.-->
                The missing statistics tool<br class="hidden sm:block">
                for your&nbsp;<span class="text-indigo-500">Laravel</span> application.
<!--                V3 A simple statistics tool for <span class="text-indigo-500">Laravel</span><br>to check the ROI of your campaigns in seconds.-->
            </h1>
            <p class="mb-6 font-light text-gray-500 lg:mb-8 md:text-lg lg:text-xl dark:text-gray-400 px-6 sm:px-0">
<!--                V1 Get highly detailed user, revenue and campaign statistics in no time.<br>-->
<!--                Start knowing your users and campaigns today!-->
<!--                V2 Finally a lightweight and GDPR-compliant statistics tool for Laravel,<br>-->
<!--                which quickly reveals marketing ROI and user conversion with a very simple setup.-->
<!--                V3 Quickly reveal marketing ROI and<br>user conversion efficiency with a minimal setup.-->
                Transcending mere counts of visits and views and discover in-depth metrics like<br class="hidden sm:block">
                Registrations, Daily Active Users, New Paying Users, campaign ROI and much more in just <a href="/docs" target="_blank" class="font-bold text-indigo-500">5 minutes</a>!
            </p>
            <Link v-if="$page.props.canRegister" :href="route('register')" class="inline-flex items-center justify-center px-5 py-3 mr-3 text-base font-medium text-center text-white rounded-lg bg-indigo-600 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 dark:focus:ring-indigo-900">
                Get started
                <svg class="w-5 h-5 ml-2 -mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
            </Link>
            <a v-if="$page.props.canLogin" href="https://demo.simplestats.io/login" :target="(appEnv === 'demo') ? '_self' : '_blank'" class="inline-flex items-center justify-center px-5 py-3 text-base font-medium text-center text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:text-white dark:border-gray-700 dark:hover:bg-gray-700 dark:focus:ring-gray-800">
                Live demo
            </a>
        </section>

        <Screenshot />

        <Features />

        <Testimonials />

        <Pricing :plans="plans" />

        <SelfHosted />
    </div>
</template>
