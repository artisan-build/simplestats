<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SectionBorder from "@/Components/SectionBorder.vue";
import ActionTable from "@/Components/ActionTable.vue";
import CreateDataImportForm from "@/Pages/DataImports/Partials/CreateDataImportForm.vue";
import {ArrowPathIcon, CheckCircleIcon, XCircleIcon} from "@heroicons/vue/24/outline/index.js";

defineProps({
    filters: Object,
    projects: Array,
    instances: Array,
    dataImports: Object,
    importTypes: Array,
    mimeTypes: Array,
    permissions: Object,
});

const getTrackedJobStatusOptions = (trackedJob) => {
    const statusOptions = {
        icon: '',
        text: trackedJob.status,
        color: '',
        svgClass: '',
    };

    switch (trackedJob.status) {
        case 'queued':
        case 'retrying':
        case 'started':
            statusOptions.icon = ArrowPathIcon;
            statusOptions.color = 'text-indigo-600';
            statusOptions.svgClass = 'animate-spin';
            break;
        case 'finished':
            statusOptions.icon = CheckCircleIcon;
            statusOptions.color = 'text-green-600';
            break;
        case 'failed':
            statusOptions.icon = XCircleIcon;
            statusOptions.color = 'text-red-600';
            break;
    }

    return statusOptions;
};

</script>

<template>
    <AppLayout title="Data Imports">
        <template #header>
            Data Imports
        </template>

        <div>
            <div>
                <CreateDataImportForm
                    :filters="filters"
                    :projects="projects"
                    :instances="instances"
                    :dataImports="dataImports"
                    :importTypes="importTypes"
                    :mimeTypes="mimeTypes"
                    :permissions="permissions"
                />

                <div v-if="dataImports.total > 0">
                    <SectionBorder />

                    <div class="mt-10 sm:mt-0">
                        <ActionTable :links="dataImports.links" >
                            <template #title>
                                Your Data Imports
                            </template>

                            <template #description>
                                Manage your existing Data Imports.
                            </template>

                            <template #content>
                                <tr class="text-left font-bold">
                                    <th class="pb-4 pt-6 px-6">Status</th>
                                    <th class="pb-4 pt-6 px-6">Project</th>
                                    <th class="pb-4 pt-6 px-6">Instance</th>
                                    <th class="pb-4 pt-6 px-6">Type</th>
                                    <th class="pb-4 pt-6 px-6">Creator</th>
                                    <th class="pb-4 pt-6 px-6">Rows imported</th>
                                    <th class="pb-4 pt-6 px-6">From Id</th>
                                    <th class="pb-4 pt-6 px-6">To Id</th>
                                </tr>
                                <tr v-for="dataImport in dataImports.data" :key="dataImport.id">
                                    <td class="border-t px-6 py-4">
                                        <div class="flex items-center" :class="getTrackedJobStatusOptions(dataImport.tracked_job).color">
                                            <div class="mr-4">
                                                <component
                                                    :class="getTrackedJobStatusOptions(dataImport.tracked_job).svgClass"
                                                    class="inline-block h-6 w-6" :is="getTrackedJobStatusOptions(dataImport.tracked_job).icon"
                                                />
                                            </div>
                                            <div>
                                                <div class="font-medium">
                                                    Import {{ getTrackedJobStatusOptions(dataImport.tracked_job).text }}
                                                </div>
                                                <div class="text-sm">
                                                    <span v-if="dataImport.tracked_job.finished_at">
                                                        At {{ new Date(dataImport.tracked_job.finished_at).toLocaleString() }}
                                                    </span>
                                                    <span v-else-if="dataImport.tracked_job.started_at">
                                                        At {{ new Date(dataImport.tracked_job.started_at).toLocaleString() }}
                                                    </span>
                                                    <span v-else>
                                                        At {{ new Date(dataImport.tracked_job.created_at).toLocaleString() }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="border-t px-6 py-4 focus:text-indigo-500">
                                        {{ dataImport.project.name }}
                                    </td>
                                    <td class="border-t px-6 py-4 focus:text-indigo-500">
                                        {{ dataImport.instance.name }}
                                    </td>
                                    <td class="border-t px-6 py-4 focus:text-indigo-500">
                                        {{ dataImport.type }}
                                    </td>
                                    <td class="border-t px-6 py-4 focus:text-indigo-500">
                                        {{ dataImport.created_by.name }}
                                    </td>
                                    <td class="border-t px-6 py-4 focus:text-indigo-500">
                                        {{ dataImport.rows_imported ?? '...' }}
                                    </td>
                                    <td class="border-t px-6 py-4 focus:text-indigo-500">
                                        {{ dataImport.id_from ?? '...' }}
                                    </td>
                                    <td class="border-t px-6 py-4 focus:text-indigo-500">
                                        {{ dataImport.id_to ?? '...' }}
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
