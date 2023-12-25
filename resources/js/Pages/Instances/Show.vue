<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteInstanceForm from '@/Pages/Instances/Partials/DeleteInstanceForm.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import UpdateInstanceForm from '@/Pages/Instances/Partials/UpdateInstanceForm.vue';

defineProps({
    instance: Object,
    projects: Array,
    permissions: Object,
});
</script>

<template>
    <AppLayout title="Instance">
        <template #header>
            Instance: {{ instance.name }}
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <UpdateInstanceForm :instance="instance" :projects="projects" :permissions="permissions" />

                <template v-if="permissions.canDeleteInstance">
                    <SectionBorder />

                    <DeleteInstanceForm class="mt-10 sm:mt-0" :instance="instance" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
