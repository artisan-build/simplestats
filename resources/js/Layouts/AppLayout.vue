<script setup>
import { ref } from 'vue';
import {Head} from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import Navigation from "@/Components/Navigation.vue";
import Footer from "@/Components/Footer.vue";
import MenuToggler from "@/Components/MenuToggler.vue";
import EnvBadge from "@/Components/EnvBadge.vue";

defineProps({
    title: String,
    fullscreen: {
        type: Boolean,
        default: true,
    },
});

const sidebarIsOpen = ref(false);
</script>

<template>
    <div class="flex min-h-screen w-full overflow-x-clip">
        <Head :title="title" />

        <Banner />

        <div @click="sidebarIsOpen = false" v-show="sidebarIsOpen" class="fixed inset-0 z-30 bg-gray-950/50 transition duration-500 dark:bg-gray-950/75 lg:hidden"></div>

        <aside
            class="overflow-y-auto bg-white px-3 fixed border-r shadow inset-y-0 start-0 z-30 grid h-screen content-between transition-all dark:bg-gray-900 -translate-x-full rtl:translate-x-full"
            :class="{
                'lg:z-0 lg:ring-0 lg:transition-none lg:translate-x-0 rtl:lg:-translate-x-0 lg:sticky': $page.props.auth.user.settings?.showMenu ?? true,
                'translate-x-0 shadow-xl ring-1 ring-gray-950\/5 rtl:-translate-x-0 dark:ring-white\/10 lg:sticky': sidebarIsOpen,
            }"
        >
                <Navigation />
        </aside>

        <div class="w-screen flex flex-1 flex-col bg-gray-50">
            <div class="lg:hidden px-6 py-3 -mb-6">
                <button class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out" @click="sidebarIsOpen = ! sidebarIsOpen">
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
            </div>
            <main class="mx-auto h-full w-full p-8" :class="{'max-w-screen-2xl': ! fullscreen}">
                <header v-if="$slots.header" class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between gap-b-8 pb-8">
                    <h1 class="flex items-center w-full text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
                        <div v-if="! ($page.props.auth.user.settings?.showMenu ?? true)">
                            <MenuToggler />
                        </div>
                        <div class="flex flex-grow justify-between">
                            <slot name="header" />
                        </div>
                        <EnvBadge />
                    </h1>
                </header>

                <slot />
            </main>
            <Footer v-if="$page.props.appType === 'default'" />
        </div>
    </div>
</template>
