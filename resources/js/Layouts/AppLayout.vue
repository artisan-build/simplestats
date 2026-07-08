<script setup>
import {onMounted, ref} from 'vue';
import {Head} from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import LoginSummaryModal from '@/Components/LoginSummaryModal.vue';
import Navigation from "@/Components/Navigation.vue";
import AiChatPanel from '@/Components/AiChat/AiChatPanel.vue';
import TeamDropdown from "@/Pages/Teams/Partials/TeamDropdown.vue";
import {useAiChat} from '@/Composables/useAiChat.js';
import ListIcon from 'bootstrap-icons/icons/list.svg?component';
import CloseIcon from 'bootstrap-icons/icons/x-lg.svg?component';

defineProps({
    title: String,
    fullscreen: {
        type: Boolean,
        default: true,
    },
});

const sidebarIsOpen = ref(false);

const aiChat = useAiChat();

onMounted(aiChat.initFromStorage);
</script>

<template>
    <div class="flex min-h-screen w-full overflow-x-clip">
        <Head :title="title" />

        <Banner />

        <LoginSummaryModal />

        <div v-if="$page.props.onboardingPassed" @click="sidebarIsOpen = false" v-show="sidebarIsOpen" class="fixed inset-0 z-30 bg-gray-950/50 transition duration-500 dark:bg-dark-950/75 lg:hidden"></div>

        <aside v-if="$page.props.onboardingPassed"
            class="bg-white dark:bg-dark-800 px-3 fixed border-r dark:border-dark-700 shadow inset-y-0 start-0 z-30 grid h-screen content-between transition-all -translate-x-full rtl:translate-x-full lg:transition-none lg:translate-x-0 rtl:lg:-translate-x-0 lg:sticky"
            :class="{
                'translate-x-0 shadow-xl rtl:-translate-x-0 lg:sticky': sidebarIsOpen,
            }"
        >
                <Navigation />
        </aside>

        <div class="w-screen flex flex-1 flex-col bg-gray-50 dark:bg-dark-900">
            <div class="lg:hidden px-6 py-3 -mb-6">
                <button v-if="$page.props.onboardingPassed" @click="sidebarIsOpen = ! sidebarIsOpen" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-dark-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-dark-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                    <ListIcon v-if="! sidebarIsOpen" class="size-6" />
                    <CloseIcon v-else class="size-6" />
                </button>
            </div>
            <main class="mx-auto h-full w-full p-4" :class="fullscreen ? 'max-w-screen-2xl' : 'max-w-screen-2xl'">
                <header v-if="$slots.header" class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between gap-b-4 pb-4">
                    <h1 class="flex items-center w-full text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
                        <div class="flex flex-grow justify-between items-center max-w-3xl mx-auto">
                            <slot name="header" />
                            <div v-if="$page.props.auth.user.all_teams.length > 1">
                                <div class="flex items-center">
                                    <div class="text-sm pt-2 mr-3">
                                        Current Team:
                                    </div>
                                    <TeamDropdown />
                                </div>
                            </div>
                        </div>
                    </h1>
                </header>

                <slot />
            </main>
        </div>

        <template v-if="$page.props.onboardingPassed && aiChat.isOpen.value">
            <!-- Desktop: inline column pushing the content; mobile: overlay drawer -->
            <aside class="hidden lg:flex w-96 shrink-0 flex-col sticky top-0 h-screen bg-white dark:bg-dark-800 border-l dark:border-dark-700">
                <AiChatPanel />
            </aside>
            <div @click="aiChat.setOpen(false)" class="fixed inset-0 z-30 bg-gray-950/50 dark:bg-dark-950/75 lg:hidden"></div>
            <aside class="fixed inset-y-0 end-0 z-40 flex w-full max-w-sm flex-col bg-white dark:bg-dark-800 border-l dark:border-dark-700 shadow-xl lg:hidden">
                <AiChatPanel />
            </aside>
        </template>
    </div>
</template>
