<script setup>
import {Link} from "@inertiajs/vue3";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Features from "@/Pages/Home/Partials/Features.vue";

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const appName = import.meta.env.VITE_APP_NAME;
</script>

<template>
    <div v-if="canLogin" class="bg-white z-10 relative">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 pt-6">
            <a href="/" class="inline-flex items-center">
                <ApplicationLogo />
                <span class="ml-3 inline-flex text-xl font-bold leading-5 tracking-tight text-gray-950 dark:text-white">
                    {{ appName }}
                </span>
            </a>

            <div class="flex items-center gap-6">
                <Link :href="'#features'" class="">Features</Link>

                <Link href="#" class="">Docs</Link>

                <Link v-if="$page.props.auth.user" :href="route('stats.details')" class=" text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">Dashboard</Link>

                <template v-else>
                    <Link :href="route('login')" class="">Log in</Link>

                    <Link v-if="canRegister" :href="route('register')" class="border-indigo-700 text-indigo-700 hidden rounded-md border px-8 py-3 text-center font-bold md:inline-block">Get Started</Link>
                </template>
            </div>
        </div>
    </div>

    <div class="min-h-screen bg-white dark:bg-gray-900">
        <section class="text-center pt-24">
            <h1 class="mb-10 text-4xl font-extrabold tracking-tight leading-none md:text-5xl xl:text-6xl dark:text-white">
                The missing statistic tool<br/>for <span class="text-indigo-500">Laravel</span> apps.
            </h1>
            <p class="mb-6 font-light text-gray-500 lg:mb-8 md:text-lg lg:text-xl dark:text-gray-400">
                Get highly detailed user, revenue and campaign statistics in no time.<br>
                Start knowing your users and campaigns today!
            </p>
            <Link v-if="canRegister" :href="route('register')" class="inline-flex items-center justify-center px-5 py-3 mr-3 text-base font-medium text-center text-white rounded-lg bg-indigo-600 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 dark:focus:ring-indigo-900">
                Get started
                <svg class="w-5 h-5 ml-2 -mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
            </Link>
            <a href="https://demo.simplestats.io/login" target="_blank" class="inline-flex items-center justify-center px-5 py-3 text-base font-medium text-center text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:text-white dark:border-gray-700 dark:hover:bg-gray-700 dark:focus:ring-gray-800">
                Show Demo
            </a>
        </section>
        <section class="mt-5 flex justify-center px-4 md:mt-12 xl:mt-0">
            <div class="w-full max-w-screen-xl xl:p-8 xl:py-16">
                <img src="images/screenshot.png" class="w-full rounded sm:rounded-lg md:rounded-xl shadow-xl border" alt="Screenshot">
            </div>
        </section>
        <section class="flex justify-center px-4 pb-32">
            <Features />
        </section>
    </div>
</template>
