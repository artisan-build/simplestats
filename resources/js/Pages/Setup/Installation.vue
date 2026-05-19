<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import FullscreenCard from "@/Components/FullscreenCard.vue";
import {router} from "@inertiajs/vue3";
import {copyToClipboard} from "@/Helpers/ClipboardHelpers.js";

const props = defineProps({
    apiToken: String,
});

const appName = import.meta.env.VITE_APP_NAME;
const appType = import.meta.env.VITE_APP_TYPE;
const isSelfHosted = appType !== 'saas';

const submit = () => {
    router.get(route('dashboard'));
};

const composerInstallCmd = 'composer require simplestats-io/laravel-client';
const publishConfigCmd = 'php artisan vendor:publish --tag="simplestats-client-config"';

const apiUrl = `${window.location.origin}/api/v1`;

const envBlock = isSelfHosted
    ? `SIMPLESTATS_API_URL=${apiUrl}\nSIMPLESTATS_API_TOKEN=${props.apiToken}`
    : `SIMPLESTATS_API_TOKEN=${props.apiToken}`;
</script>

<template>
    <FullscreenCard title="Installation" description="Complete the setup.">
        <form @submit.prevent="submit">
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Install the {{ appName }} client
                </label>
                <div class="flex justify-between bg-gray-100 dark:bg-dark-900 px-4 py-2 rounded font-mono text-sm text-gray-500 dark:text-dark-400 break-all">
                    <div class="truncate">
                        {{ composerInstallCmd }}
                    </div>
                    <div>
                        <button type="button" @click="copyToClipboard(composerInstallCmd)">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 -my-1.5 hover:scale-110">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Publish the config
                </label>
                <div class="flex justify-between bg-gray-100 dark:bg-dark-900 px-4 py-2 rounded font-mono text-sm text-gray-500 dark:text-dark-400 break-all">
                    <div class="truncate">
                        {{ publishConfigCmd }}
                    </div>
                    <div>
                        <button type="button" @click="copyToClipboard(publishConfigCmd)">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 -my-1.5 hover:scale-110">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Add the following credentials to your environment file
                </label>
                <div class="flex justify-between bg-gray-100 dark:bg-dark-900 px-4 py-2 rounded font-mono text-sm text-gray-500 dark:text-dark-400 break-all">
                    <div class="min-w-0 flex-1">
                        <div v-if="isSelfHosted" class="truncate">
                            <span class="text-blue-600 dark:text-blue-500">SIMPLESTATS_API_URL=</span><span class="text-green-600 dark:text-green-500">{{ apiUrl }}</span>
                        </div>
                        <div class="truncate">
                            <span class="text-blue-600 dark:text-blue-500">SIMPLESTATS_API_TOKEN=</span><span class="text-green-600 dark:text-green-500">{{ apiToken }}</span>
                        </div>
                    </div>
                    <div>
                        <button type="button" @click="copyToClipboard(envBlock)">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 -my-1.5 hover:scale-110">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Next steps
                </label>
                <p class="text-sm text-gray-600 dark:text-dark-400 mb-2">
                    To enable <strong>advanced tracking</strong> or for further instructions, check the published config or the setup guide in the <a class="simpleLink underline" href="/docs" target="_blank">documentation</a>.
                </p>
            </div>

            <div class="mt-8">
                <PrimaryButton class="w-full !text-sm justify-center !p-3">
                    Complete
                </PrimaryButton>
            </div>
        </form>
    </FullscreenCard>
</template>
