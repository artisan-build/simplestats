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

defineProps({
    projects: Array,
});

const createProjectForm = useForm({
    name: '',
});

const createProject = () => {
    createProjectForm.post(route('projects.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createProjectForm.reset();
        },
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
                            Create a new Project and after that, add an new Instance to it!
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

                    <div v-if="projects.total > 0">
                        <SectionBorder />

                        <div class="mt-10 sm:mt-0">
                            <ActionTable :links="projects.links" >
                                <template #title>
                                    Your Projects
                                </template>

                                <template #description>
                                    Manage your existing Projects.
                                </template>

                                <template #content>
                                    <tr class="text-left font-bold">
                                        <th class="pb-4 pt-6 px-6">Name</th>
                                    </tr>
                                    <tr v-for="project in projects.data" :key="project.id" class="hover:bg-gray-50 focus-within:bg-gray-50 text-gray-700">
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
        </div>
    </AppLayout>
</template>
