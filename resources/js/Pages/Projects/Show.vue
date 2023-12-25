<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteProjectForm from '@/Pages/Projects/Partials/DeleteProjectForm.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import UpdateProjectForm from '@/Pages/Projects/Partials/UpdateProjectForm.vue';
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
            Project: {{ project.name }}
        </template>

        <div>
            <UpdateProjectForm :project="project" :permissions="permissions" />

            <div>
                <SectionBorder />

                <ActionTable :links="[]">
                    <template #title>
                        Project Instances
                    </template>

                    <template #description>
                        Manage the Projects associated Instances.
                    </template>

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
