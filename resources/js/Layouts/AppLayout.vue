<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import TeamDropdown from "@/Pages/Teams/Partials/TeamDropdown.vue";
import ProfileDropdown from "@/Pages/Profile/Partials/ProfileDropdown.vue";
import Navigation from "@/Components/Navigation.vue";

defineProps({
    title: String,
    fullscreen: Boolean,
});

const sidebarIsOpen = ref(false);

const smallAside = true;

const appName = import.meta.env.VITE_APP_NAME;
</script>

<template>
    <div class="flex min-h-screen w-full overflow-x-clip">
        <Head :title="title" />

        <Banner />

        <div @click="sidebarIsOpen = false" v-show="sidebarIsOpen" class="fixed inset-0 z-30 bg-gray-950/50 transition duration-500 dark:bg-gray-950/75 lg:hidden"></div>

        <aside :class="{
                'translate-x-0 shadow-xl ring-1 ring-gray-950\/5 rtl:-translate-x-0 dark:ring-white\/10 lg:sticky': sidebarIsOpen,
                '-translate-x-full rtl:translate-x-full lg:sticky': ! sidebarIsOpen
            }" class="fixed inset-y-0 start-0 z-30 grid h-screen content-start bg-white transition-all dark:bg-gray-900 lg:z-0 lg:bg-transparent lg:shadow-none lg:ring-0 lg:transition-none dark:lg:bg-transparent lg:translate-x-0 rtl:lg:-translate-x-0 -translate-x-full rtl:translate-x-full lg:sticky">
            <div class="overflow-x-clip">
                <header class="flex h-16 items-center bg-white px-4 ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 lg:shadow-sm">
                    <div class="flex items-center">
                        <Link :href="route('dashboard')">
                            <ApplicationMark class="block h-9 w-auto" />
                        </Link>
                        <Link :href="route('dashboard')"
                              class="ml-3 inline-flex text-xl font-bold leading-5 tracking-tight text-gray-950 dark:text-white"
                              :class="{'hidden': smallAside}"
                        >
                            {{ appName }}
                        </Link>
                    </div>
                </header>
            </div>

            <nav class="flex flex-col gap-y-7 overflow-y-auto overflow-x-hidden px-6 py-8" style="scrollbar-gutter: stable">
                <Navigation />
            </nav>
        </aside>

        <div class="w-screen flex flex-1 flex-col">
            <div class="sticky top-0 z-20 overflow-x-clip">
                <nav class="flex h-16 items-center gap-x-4 bg-white px-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 md:px-6 lg:px-8">
                    <button class="lg:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out" @click="sidebarIsOpen = ! sidebarIsOpen">
                        <svg
                            class="h-6 w-6"
                            stroke="currentColor"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <path
                                :class="{'hidden': sidebarIsOpen, 'inline-flex': ! sidebarIsOpen }"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                            <path
                                :class="{'hidden': ! sidebarIsOpen, 'inline-flex': sidebarIsOpen }"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>

                    <Link :href="route('dashboard')" class="inline-flex text-xl font-bold leading-5 tracking-tight text-gray-950 dark:text-white">
                        {{ appName }}
                    </Link>

                    <div class="ms-auto flex items-center gap-x-4">
                        <div class="ms-3 relative">
                            <TeamDropdown />
                        </div>

                        <!-- Settings Dropdown -->
                        <div class="ms-3 relative">
                            <ProfileDropdown />
                        </div>
                    </div>
                </nav>
            </div>
            <main class="mx-auto h-full w-full p-8" :class="{'max-w-screen-2xl': ! fullscreen}">
                <header v-if="$slots.header" class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between gap-b-8 pb-8">
                    <h1 class="flex items-center justify-between w-full text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
                        <slot name="header" />
                    </h1>
                </header>

                <slot />
            </main>
        </div>
    </div>
</template>
