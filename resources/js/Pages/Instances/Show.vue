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

defineProps({
    instance: Object,
    projects: Array,
    permissions: Object,
});

const page = usePage();

const displayingToken = ref(false);

watchEffect(() => {
    displayingToken.value = page.props.jetstream.flash.token;
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

const regenerateApiTokenForm = useForm({});
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
</script>

<template>
    <AppLayout title="Instance">
        <template #header>
            <div>
                Instance: {{ instance.name }}
            </div>
            <div>
                <PrimaryButton class="h-10 bg-indigo-500 hover:bg-indigo-600 focus:bg-indigo-600" type="button" @click="$inertia.get(route('stats.details', {projectId: instance.project_id, instanceId: instance.id}))">
                    Go to Stats
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 ml-3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                    </svg>
                </PrimaryButton>
            </div>
        </template>

        <div>
            <UpdateInstanceForm :instance="instance" :projects="projects" :permissions="permissions" />

            <div v-if="instance.tokens.length > 0">
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
                                            Token
                                        </button>

                                        <button class="cursor-pointer ms-6 text-sm text-red-500" @click="confirmApiTokenRegeneration(instance)">
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
                        Please copy your new API token and past it to your App Instance you want to track!
                    </div>

                    <div class="mt-4 bg-gray-100 dark:bg-gray-900 px-4 py-2 rounded font-mono text-sm text-gray-500 break-all">
                        {{ $page.props.jetstream.flash.token }}
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
