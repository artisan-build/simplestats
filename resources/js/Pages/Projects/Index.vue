<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from "@/Components/InputLabel.vue";
import ActionMessage from "@/Components/ActionMessage.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputError from "@/Components/InputError.vue";
import FormSection from "@/Components/FormSection.vue";
import Checkbox from "@/Components/Checkbox.vue";
import TextInput from "@/Components/TextInput.vue";
import {useForm} from "@inertiajs/vue3";
import SectionBorder from "@/Components/SectionBorder.vue";
import ActionSection from "@/Components/ActionSection.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";
import {ref} from "vue";

defineProps({
    projects: Array,
});

const createProjectForm = useForm({
    name: '',
});

const deleteProjectForm = useForm({});

const projectBeingDeleted = ref(null);

const createProject = () => {
    createProjectForm.post(route('projects.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createProjectForm.reset();
        },
    });
};

const confirmProjectDeletion = (project) => {
    projectBeingDeleted.value = project;
};

const deleteProject = () => {
    deleteProjectForm.delete(route('projects.destroy', projectBeingDeleted.value), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (projectBeingDeleted.value = null),
    });
};

</script>

<template>
    <AppLayout title="Projects">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Projects
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <div>
                    <FormSection @submitted="createProject">
                        <template #title>
                            Create new Project
                        </template>

                        <template #description>
                            Here you can create a project and then add some instances!
                        </template>

                        <template #form>
                            <div class="col-span-6 sm:col-span-4">
                                <InputLabel for="name" value="Name" />
                                <TextInput
                                    id="name"
                                    v-model="createProjectForm.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    autofocus
                                />
                                <InputError :message="createProjectForm.errors.name" class="mt-2" />
                            </div>
                        </template>

                        <template #actions>
                            <ActionMessage :on="createProjectForm.recentlySuccessful" class="me-3">
                                Created.
                            </ActionMessage>

                            <PrimaryButton :class="{ 'opacity-25': createProjectForm.processing }" :disabled="createProjectForm.processing">
                                Create
                            </PrimaryButton>
                        </template>
                    </FormSection>

                    <div v-if="projects.length > 0">
                        <SectionBorder />

                        <div class="mt-10 sm:mt-0">
                            <ActionSection>
                                <template #title>
                                    Your Projects
                                </template>

                                <template #description>
                                    You may delete any of your existing projects if they are no longer needed.
                                </template>

                                <template #content>
                                    <div class="space-y-6">
                                        <div v-for="project in projects" :key="project.id" class="flex items-center justify-between">
                                            <div class="break-all dark:text-white">
                                                {{ project.name }}
                                            </div>

                                            <div class="flex items-center ms-2">
                                                <div v-if="project.last_used_ago" class="text-sm text-gray-400">
                                                    Last used {{ project.last_used_ago }}
                                                </div>

                                                <button class="cursor-pointer ms-6 text-sm text-red-500" @click="confirmProjectDeletion(project)">
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </ActionSection>
                        </div>
                    </div>

                    <ConfirmationModal :show="projectBeingDeleted != null" @close="projectBeingDeleted = null">
                        <template #title>
                            Delete Project
                        </template>

                        <template #content>
                            Are you sure you would like to delete this Project?<br>
                            Project: "{{ projectBeingDeleted.name }}"
                        </template>

                        <template #footer>
                            <SecondaryButton @click="projectBeingDeleted = null">
                                Cancel
                            </SecondaryButton>

                            <DangerButton
                                class="ms-3"
                                :class="{ 'opacity-25': deleteProjectForm.processing }"
                                :disabled="deleteProjectForm.processing"
                                @click="deleteProject"
                            >
                                Delete
                            </DangerButton>
                        </template>
                    </ConfirmationModal>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
