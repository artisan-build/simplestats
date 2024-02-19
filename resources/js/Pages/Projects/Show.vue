<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteProjectForm from '@/Pages/Projects/Partials/DeleteProjectForm.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import UpdateProjectForm from '@/Pages/Projects/Partials/UpdateProjectForm.vue';
import ShowInstancesList from "@/Pages/Instances/Partials/ShowInstancesList.vue";
import ActionTable from "@/Components/ActionTable.vue";
import InputError from "@/Components/InputError.vue";
import FormSection from "@/Components/FormSection.vue";
import TextInput from "@/Components/TextInput.vue";
import ActionMessage from "@/Components/ActionMessage.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SelectInput from "@/Components/SelectInput.vue";
import {useForm} from "@inertiajs/vue3";
import CreateProjectInstanceForm from "@/Pages/Projects/Partials/CreateProjectInstanceForm.vue";

defineProps({
    project: Object,
    permissions: Object,
});

</script>

<template>
    <AppLayout title="Project">
        <template #header>
            Project: {{ project.name }}
        </template>

        <div>
            <UpdateProjectForm :project="project" :permissions="permissions" />

            <SectionBorder />

            <div class="mb-6">
                <CreateProjectInstanceForm :permissions="permissions" :project="project" />
            </div>

            <div>
                <ActionTable :links="[]">
                    <template #content>
                        <ShowInstancesList :insideProject="project" :instances="project.instances" />
                    </template>
                </ActionTable>
            </div>

            <template v-if="permissions.canDeleteProject">
                <SectionBorder />

                <DeleteProjectForm class="mt-10 sm:mt-0" :project="project" />
            </template>
        </div>
    </AppLayout>
</template>
