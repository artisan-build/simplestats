<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteProjectForm from '@/Pages/Projects/Partials/DeleteProjectForm.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import UpdateProjectNameForm from '@/Pages/Projects/Partials/UpdateProjectNameForm.vue';
import ShowInstancesList from "@/Pages/Instances/Partials/ShowInstancesList.vue";
import ActionTable from "@/Components/ActionTable.vue";

defineProps({
    project: Object,
    permissions: Object,
});
</script>

<template>
    <AppLayout title="Project">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ project.name }}
            </h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <UpdateProjectNameForm :project="project" :permissions="permissions" />

                <div v-if="project.instances.length > 0">
                    <SectionBorder />

                    <ActionTable :links="[]">
                        <template #title>
                            Project Instances
                        </template>

                        <template #description>
                            Manage the Projects associated Instances.
                        </template>

                        <template #content>
                            <ShowInstancesList :instances="project.instances" />
                        </template>
                    </ActionTable>
                    </div>

                <template v-if="permissions.canDeleteProject">
                    <SectionBorder />

                    <DeleteProjectForm class="mt-10 sm:mt-0" :project="project" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
