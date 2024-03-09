<script setup>

import WarningBox from "@/Components/CustomIcons/WarningBox.vue";
import {useForm} from "@inertiajs/vue3";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";
import {ref} from "vue";
import DangerButton from "@/Components/DangerButton.vue";

const props = defineProps({
    dataImports: Object,
    permissions: Object,
});

const cancelDataImportForm = useForm({});

const cancelDataImport = () => {
    cancelDataImportForm.post(route('data-import.cancel', props.dataImports.data[0]), {
        preserveScroll: true,
        onFinish: () => {
            dataImportBeeingCanceled.value = false;
        },
    });
};

const dataImportBeeingCanceled = ref(false);
const confirmDataImportCancelation = () => {
    dataImportBeeingCanceled.value = true;
};
</script>

<template>
    <WarningBox>
        You can only create <strong>one</strong> import at a time.
        Please wait until this import has finished before starting another data import.
        We'll send you an <strong>email</strong> as soon as the current import is complete!
        Depending on the amount of data, this may take from minutes to hours.
        <div v-if="permissions.canCancelDataImport" class="mt-6">
            <form @submit.prevent="confirmDataImportCancelation">
                <button type="submit" class="text-red-700 hover:underline font-medium text-base">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 inline-block mr-1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>Click here to cancel the current data import
                </button>
            </form>
        </div>
    </WarningBox>

    <ConfirmationModal :show="dataImportBeeingCanceled" @close="dataImportBeeingCanceled = false">
        <template #title>
            Cancel Data Import
        </template>

        <template #content>
            Are you sure you want to cancel the data import?
        </template>

        <template #footer>
            <SecondaryButton @click="dataImportBeeingCanceled = false">
                Nevermind
            </SecondaryButton>

            <DangerButton
                class="ms-3"
                :class="{ 'opacity-25': cancelDataImportForm.processing }"
                :disabled="cancelDataImportForm.processing"
                @click="cancelDataImport"
            >
                Cancel Import
            </DangerButton>
        </template>
    </ConfirmationModal>
</template>

<style scoped>

</style>
