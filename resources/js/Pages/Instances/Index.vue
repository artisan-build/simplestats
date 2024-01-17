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
import {useForm} from "@inertiajs/vue3";
import SelectInput from "@/Components/SelectInput.vue";
import ShowInstancesList from "@/Pages/Instances/Partials/ShowInstancesList.vue";

const props = defineProps({
    instances: Object,
    projects: Array,
    project: Object,
});

const createInstanceForm = useForm({
    name: '',
    project_id: props.project.id ?? '',
});

const createInstance = () => {
    createInstanceForm.post(route('projects.instances.store', createInstanceForm.project_id), {
        preserveScroll: true,
        onSuccess: () => {
            createInstanceForm.reset();
        },
    });
};

</script>

<template>
    <AppLayout title="Instances">
        <template #header>
            Instances
        </template>

        <div>
            <FormSection @submitted="createInstance">
                <template #title>
                    Create new Instance
                </template>

                <template #description>
                    Here you can create a new Instance for a given Project.
                </template>

                <template #form>
                    <div class="col-span-6 sm:col-span-4">
                        <InputLabel for="name" value="Name" />
                        <TextInput
                            id="name"
                            v-model="createInstanceForm.name"
                            type="text"
                            class="mt-1 block w-full"
                            autofocus
                        />
                        <InputError :message="createInstanceForm.errors.name" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-4">
                        <InputLabel for="project_id" value="Project" />
                        <SelectInput
                            id="project_id"
                            v-model="createInstanceForm.project_id"
                            class="mt-1 block w-full"
                            :options="projects"
                        />
                        <InputError :message="createInstanceForm.errors.project_id" class="mt-2" />
                    </div>
                </template>

                <template #actions>
                    <ActionMessage :on="createInstanceForm.recentlySuccessful" class="me-3">
                        Created.
                    </ActionMessage>

                    <PrimaryButton :class="{ 'opacity-25': createInstanceForm.processing }" :disabled="createInstanceForm.processing">
                        Create
                    </PrimaryButton>
                </template>
            </FormSection>

            <div v-if="instances.total > 0">
                <SectionBorder />

                <div class="mt-10 sm:mt-0">
                    <ActionTable :links="instances.links">
                        <template #title>
                            Your Instances
                        </template>

                        <template #description>
                            Manage your existing Instances.
                        </template>

                        <template #content>
                            <ShowInstancesList :instances="instances.data" />
                        </template>
                    </ActionTable>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
