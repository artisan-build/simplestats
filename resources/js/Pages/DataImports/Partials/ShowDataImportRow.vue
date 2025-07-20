<script setup>
import {useForm} from "@inertiajs/vue3";
import {ref} from "vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {truncate} from "lodash";
import {
    ArrowPathIcon,
    ArrowUturnLeftIcon,
    CheckCircleIcon,
    ClockIcon,
    QuestionMarkCircleIcon,
    XCircleIcon,
    XMarkIcon,
} from "@heroicons/vue/24/outline/index.js";

const props = defineProps({
    projects: Array,
    instances: Array,
    dataImport: Object,
});

const handleDataImportForm = useForm({});

const handleDataImport = () => {
    let action = dataImportBeeingHandled.value;

    handleDataImportForm.post(route('data-import.'+action, props.dataImport), {
        preserveScroll: true,
        onFinish: () => {
            dataImportBeeingHandled.value = false;
        },
    });
};

const dataImportBeeingHandled = ref(false);
const confirmDataImportAction = (action) => {
    dataImportBeeingHandled.value = action;
};

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
        case 'rolled back':
            statusOptions.icon = ClockIcon;
            statusOptions.color = 'text-orange-600';
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
    <td class="border-t w-px px-1 py-1">
        <div class="flex items-center" :class="getTrackedJobStatusOptions(dataImport.tracked_job).color">
            <div class="mr-4 text-center">
                <component
                    :class="getTrackedJobStatusOptions(dataImport.tracked_job).svgClass"
                    class="inline-block h-6 w-6" :is="getTrackedJobStatusOptions(dataImport.tracked_job).icon"
                    v-tooltip="getTrackedJobStatusOptions(dataImport.tracked_job).text"
                />
                <span v-if="! dataImport.tracked_job.finished_at" class="block">
                    {{ dataImport.rows_progress }}%
                </span>
            </div>
            <div>
                <div class="text-sm">
                    <span v-if="dataImport.tracked_job.finished_at">
                        {{ new Date(dataImport.tracked_job.finished_at).toLocaleString() }}
                    </span>
                    <span v-else-if="dataImport.tracked_job.started_at">
                        {{ new Date(dataImport.tracked_job.started_at).toLocaleString() }}
                    </span>
                    <span v-else>
                        {{ new Date(dataImport.tracked_job.created_at).toLocaleString() }}
                    </span>
                </div>
                <div class="font-medium first-letter:uppercase">
                    {{ dataImport.type }}
                </div>
            </div>
        </div>
    </td>
    <td class="border-t px-2 py-1 focus:text-indigo-500">
        {{ truncate(dataImport.project.name, { length: 15 }) }}
    </td>
    <td class="border-t px-2 py-1 focus:text-indigo-500">
        {{ truncate(dataImport.instance.name, { length: 15 }) }}
    </td>
<!--    <td class="border-t px-2 py-1 focus:text-indigo-500">-->
<!--        {{ dataImport.type }}-->
<!--    </td>-->
    <td class="border-t px-2 py-1 focus:text-indigo-500">
        {{ truncate(dataImport.created_by.name, { length: 15 }) }}
    </td>
    <td class="border-t px-2 py-1 focus:text-indigo-500">
        {{ dataImport.rows_imported ?? 0 }}
    </td>
    <td class="border-t px-2 py-1 focus:text-indigo-500">
        {{ dataImport.id_from ?? 0 }}
    </td>
    <td class="border-t px-2 py-1 focus:text-indigo-500">
        {{ dataImport.id_to ?? 0 }}
    </td>
    <td class="border-t px-2 py-1 focus:text-indigo-500">
        <PrimaryButton v-if="! dataImport.tracked_job.finished_at" v-tooltip="'Cancel'" @click="confirmDataImportAction('cancel')">
            <XMarkIcon class="size-4" />
        </PrimaryButton>
        <DangerButton v-else-if="dataImport.tracked_job.status !== 'rolled back' && dataImport.rows_imported > 0" v-tooltip="'Rollback'" @click="confirmDataImportAction('rollback')">
            <ArrowUturnLeftIcon class="size-4" />
        </DangerButton>
        <span v-else>
            -
        </span>
        <div>
            <ConfirmationModal :show="dataImportBeeingHandled" @close="dataImportBeeingHandled = false">
                <template #title>
                    <span class="capitalize">
                        {{ dataImportBeeingHandled }} Data Import
                    </span>
                </template>

                <template #content>
                    <h3 class="text-red-500 text-lg font-bold">Warning - This action cannot be undone!</h3>
                    <br>
                    Are you sure you want to <strong>{{ dataImportBeeingHandled }}</strong> the following data import?<br>
                    <br>
                    <strong>Project:</strong> {{ projects.filter((project) => project.id == dataImport.project_id)[0].name }}<br>
                    <strong>Instance:</strong> {{ instances.filter((instance) => instance.id == dataImport.instance_id)[0].name }}<br>
                    <strong>Import Type:</strong> {{ dataImport.type }}<br>
                    <span v-if="dataImport.rows_imported > 0">
                        <strong>Rows:</strong> {{ dataImport.rows_imported }} rows - from id {{ dataImport.id_from }} to {{ dataImport.id_to }}<br>
                    </span>
                </template>

                <template #footer>
                    <SecondaryButton @click="dataImportBeeingHandled = false">
                        Nevermind
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': handleDataImportForm.processing }"
                        :disabled="handleDataImportForm.processing"
                        @click="handleDataImport"
                    >
                        {{ dataImportBeeingHandled }} Import
                    </DangerButton>
                </template>
            </ConfirmationModal>
        </div>
    </td>
</template>

<style scoped>

</style>
