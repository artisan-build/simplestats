<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import FullscreenCard from "@/Components/FullscreenCard.vue";
import {router} from "@inertiajs/vue3";
import {copyToClipboard} from "@/Helpers/ClipboardHelpers.js";
import ClipboardIcon from "bootstrap-icons/icons/clipboard.svg?component";

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
                            <ClipboardIcon class="size-5 -my-1.5 hover:scale-110" />
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
                            <ClipboardIcon class="size-5 -my-1.5 hover:scale-110" />
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
                            <ClipboardIcon class="size-5 -my-1.5 hover:scale-110" />
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
