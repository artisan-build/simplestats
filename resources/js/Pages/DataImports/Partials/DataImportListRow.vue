<script setup>

import {ArrowPathIcon, CheckCircleIcon, QuestionMarkCircleIcon, XCircleIcon} from "@heroicons/vue/24/outline/index.js";

defineProps({
    dataImport: Object,
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
            statusOptions.text = 'processing';
            statusOptions.svgClass = 'animate-spin';
            break;
        case 'finished':
            statusOptions.icon = CheckCircleIcon;
            statusOptions.text = 'completed';
            statusOptions.color = 'text-green-600';
            break;
        case 'failed':
            statusOptions.icon = XCircleIcon;
            statusOptions.color = 'text-red-600';
            break;
        case 'unknown':
            statusOptions.icon = QuestionMarkCircleIcon;
            statusOptions.color = 'text-gray-600';
            break;
    }

    return statusOptions;
};

</script>

<template>
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
</template>

<style scoped>

</style>
