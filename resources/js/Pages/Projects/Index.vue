<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from "@/Components/InputLabel.vue";
import ActionMessage from "@/Components/ActionMessage.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputError from "@/Components/InputError.vue";
import FormSection from "@/Components/FormSection.vue";
import TextInput from "@/Components/TextInput.vue";
import SectionBorder from "@/Components/SectionBorder.vue";
import ActionTable from "@/Components/ActionTable.vue";
import {Link, useForm} from "@inertiajs/vue3";
import TrackingUsageSelector from "@/Components/TrackingUsageSelector.vue";

defineProps({
    projects: Object,
    permissions: Object,
    trackingUsages: Object,
});

const createProjectForm = useForm({
    name: '',
    usage: 0,
});

const createProject = () => {
    createProjectForm.post(route('projects.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createProjectForm.reset();
        },
    });
};

const appName = import.meta.env.VITE_APP_NAME;
</script>

<template>
    <AppLayout title="Projects">
        <template #header>
            Projects
        </template>

        <div>
            <div>
                <FormSection @submitted="createProject">
                    <template #title>
                        Create new Project
                    </template>

                    <template #description>
                        Create a new project and then add a new instance to track.
                    </template>

                    <template #form>
                        <div class="col-span-6 sm:col-span-4">
                            <InputLabel for="name" value="Project name" />
                            <TextInput
                                id="name"
                                v-model="createProjectForm.name"
                                type="text"
                                class="mt-1 block w-full"
                                autofocus
                                :disabled="! permissions.canCreateProject"
                            />
                            <InputError :message="createProjectForm.errors.name" class="mt-2" />
                        </div>
                        <div class="col-span-6">
                            <InputLabel for="usage" :value="`What would you like to track?`" />
                            <div class="mt-1">
                                <TrackingUsageSelector v-model="createProjectForm.usage" :trackingUsages="trackingUsages" />
                            </div>
                            <InputError :message="createProjectForm.errors.usage" class="mt-2" />
                        </div>
                    </template>

                    <template #actions>
                        <ActionMessage :on="createProjectForm.recentlySuccessful" class="me-3">
                            Created.
                        </ActionMessage>

                        <PrimaryButton v-tooltip="!permissions.canCreateProject ? 'You are not allowed to create new projects due to plan or role limitations!' : ''"
                                       :class="{ 'opacity-25': createProjectForm.processing }"
                                       :disabled="createProjectForm.processing || !permissions.canCreateProject"
                        >
                            Create
                        </PrimaryButton>
                    </template>
                </FormSection>

                <div v-if="projects.total > 0">
                    <SectionBorder />

                    <div class="mt-10 sm:mt-0">
                        <ActionTable :links="projects.links" >
                            <template #title>
                                Your Projects
                            </template>

                            <template #description>
                                Manage your projects.
                            </template>

                            <template #content>
                                <tr class="text-left font-bold">
                                    <th class="pb-4 pt-6 px-6">Name</th>
                                </tr>
                                <tr class="hover:bg-gray-50 dark:hover:bg-dark-700 text-gray-700 dark:text-dark-300" v-for="project in projects.data" :key="project.id">
                                    <td class="border-t">
                                        <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="route('projects.show', project.id)">
                                            {{ project.name }}
                                        </Link>
                                    </td>

                                    <td class="w-px border-t">
                                        <Link class="flex items-center px-4" :href="route('projects.show', project.id)">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                            </svg>
                                        </Link>
                                    </td>
                                </tr>
                            </template>
                        </ActionTable>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
