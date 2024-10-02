<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteInstanceForm from '@/Pages/Instances/Partials/DeleteInstanceForm.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import UpdateInstanceForm from '@/Pages/Instances/Partials/UpdateInstanceForm.vue';
import ActionSection from "@/Components/ActionSection.vue";
import {ref, watchEffect} from "vue";
import {useForm, usePage} from "@inertiajs/vue3";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DialogModal from "@/Components/DialogModal.vue";
import DangerButton from "@/Components/DangerButton.vue";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";

defineProps({
    instance: Object,
    projects: Array,
    permissions: Object,
});

const page = usePage();

const displayingToken = ref(false);
const trackedVisitors = ref(0);

watchEffect(() => {
    displayingToken.value = page.props.jetstream.flash.token;
    trackedVisitors.value = page.props.jetstream.flash.trackedVisitors;
});

const viewApiTokenForm = useForm({});

const viewApiToken = (instance) => {
    viewApiTokenForm.get(route('projects.instances.token-view', [instance.project_id, instance.id]), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            viewApiTokenForm.reset();
        },
    });
};

const regenerateApiTokenForm = useForm({
    password: '',
});
const apiTokenBeingRegenerated = ref(null);

const confirmApiTokenRegeneration = (instance) => {
    apiTokenBeingRegenerated.value = instance;
};

const regenerateApiToken = () => {
    regenerateApiTokenForm.get(route('projects.instances.token-regenerate', [apiTokenBeingRegenerated.value.project_id, apiTokenBeingRegenerated.value.id]), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            apiTokenBeingRegenerated.value = null;
            regenerateApiTokenForm.reset();
        },
    });
};

const copyToClipboard = async () => {
    try {
        await navigator.clipboard.writeText(page.props.jetstream.flash.token)
        page.props.jetstream.flash = {banner: 'Token copied to clipboard!', bannerStyle: 'success'}
    } catch (err) {
        page.props.jetstream.flash = {banner: 'Token cant be copied to clipboard!', bannerStyle: 'danger'}
        // console.error('Failed to copy: ', err)
    }
}
</script>

<template>
    <AppLayout title="Instance">
        <template #header>
            <div>
                Instance: {{ instance.name }}
            </div>
            <div>
                <PrimaryButton class="h-10" type="button" @click="$inertia.get(route('dashboard', {projectId: instance.project_id, instanceId: instance.id}))">
                    Go To Dashboard
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 ml-3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                    </svg>
                </PrimaryButton>
            </div>
        </template>

        <div>
            <UpdateInstanceForm :instance="instance" :projects="projects" :permissions="permissions" />

            <div v-if="instance.tokens && instance.tokens.length > 0">
                <SectionBorder />

                <div class="mt-10 sm:mt-0">
                    <ActionSection>
                        <template #title>
                            Manage API Token
                        </template>

                        <template #description>
                            You may regenerate your existing token or view it again.
                        </template>

                        <template #content>
                            <div class="space-y-6">
                                <div v-for="token in instance.tokens" :key="token.id" class="flex items-center justify-between">
                                    <div class="break-all dark:text-white">
                                        {{ token.name }}
                                    </div>

                                    <div class="flex items-center ms-2">
                                        <div v-if="token.last_used_ago" class="text-sm text-gray-400">
                                            Last used {{ token.last_used_ago }}
                                        </div>

                                        <button
                                            class="cursor-pointer ms-6 text-sm text-gray-400 underline flex items-center"
                                            @click="viewApiToken(instance)"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 mr-1">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                            View
                                        </button>

                                        <button v-if="permissions.canRegenerateInstanceToken" class="cursor-pointer ms-6 text-sm text-red-500" @click="confirmApiTokenRegeneration(instance)">
                                            Regenerate
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </ActionSection>
                </div>
            </div>

            <template v-if="permissions.canDeleteInstance">
                <SectionBorder />

                <DeleteInstanceForm class="mt-10 sm:mt-0" :instance="instance" />
            </template>

            <!-- Token Value Modal -->
            <DialogModal :show="displayingToken" @close="displayingToken = false">
                <template #title>
                    API Token
                </template>

                <template #content>
                    <div>
                        Please copy your new API token and paste it into your <strong>.env</strong> file!
                    </div>

                    <div class="flex justify-between mt-4 bg-gray-100 dark:bg-gray-900 px-4 py-2 rounded font-mono text-sm text-gray-500 break-all">
                        <div>
                            {{ $page.props.jetstream.flash.token }}
                        </div>
                        <div>
                            <button @click="copyToClipboard">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 -my-1.5 hover:scale-110">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div v-if="trackedVisitors <= 0" class="mt-6 text-base">
                        To <strong>enable</strong> the tracking, please make sure to check out the <a href="/docs" target="_blank" class="underline hover:text-indigo-600 font-bold">documentation</a>.
                        Follow the necessary steps to get everything set up correctly!<br>

                        <br>
                        Happy analysing!
                    </div>
                </template>

                <template #footer>
                    <SecondaryButton @click="displayingToken = false">
                        Close
                    </SecondaryButton>
                </template>
            </DialogModal>

            <!-- Regenerate Token Confirmation Modal -->
            <ConfirmationModal :show="apiTokenBeingRegenerated != null" @close="apiTokenBeingRegenerated = null">
                <template #title>
                    Regenerate API Token
                </template>

                <template #content>
                    Are you sure you would like to regenerate this API token? If you already used this token, make sure to replace it afterwards in the corresponding Instance!
                    Please enter your password to confirm you would like to permanently delete this instance.

                    <div class="mt-4">
                        <TextInput
                            ref="passwordInput"
                            v-model="regenerateApiTokenForm.password"
                            type="password"
                            class="mt-1 block w-3/4"
                            placeholder="Password"
                            autocomplete="current-password"
                            @keyup.enter="regenerateApiToken"
                        />

                        <InputError :message="regenerateApiTokenForm.errors.password" class="mt-2" />
                    </div>
                </template>

                <template #footer>
                    <SecondaryButton @click="apiTokenBeingRegenerated = null">
                        Cancel
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': regenerateApiTokenForm.processing }"
                        :disabled="regenerateApiTokenForm.processing"
                        @click="regenerateApiToken"
                    >
                        Regenerate
                    </DangerButton>
                </template>
            </ConfirmationModal>
        </div>
    </AppLayout>
</template>
