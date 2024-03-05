<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import {useForm} from "@inertiajs/vue3";
import SelectInput from "@/Components/SelectInput.vue";
import {computed, ref} from "vue";
import FileInput from "@/Components/FileInput.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";

const props = defineProps({
    filters: Object,
    projects: Array,
    instances: Array,
    importTypes: Array,
    permissions: Object,
});

const createDataImportForm = useForm({
    project_id: props.filters.project_id ?? 0, //TODO reset to 0 default
    instance_id: props.filters.instance_id ?? 0,
    type: props.filters.type ?? 0,
    file: props.filters.file ?? null,
});

const createDataImport = () => {
    createDataImportForm.post(route('data-import.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createDataImportForm.reset();
            dataImportBeeingCreated.value = false;
        },
    });
};

const dataImportBeeingCreated = ref(false);
const confirmDataImportCreation = () => {
    dataImportBeeingCreated.value = true;
};

const instancesOfSelectedProject = computed(() => {
    let instances = props.instances.filter((instance) => instance.project_id == createDataImportForm.project_id);

    if (instances.length) {
        instances.unshift({
            name: 'Please select',
            id: 0,
        });
    }

    return instances;
});

</script>

<template>
    <FormSection @submitted="createDataImport">
        <template #title>
            Create new Data Import
        </template>

        <template #description>
            If your user and payment data has any conditions to count as a valid entry,
            for instance only if the user has verified his email or only if the payment has a certain status like "success",
            make sure to provide only these data in the import file.
            Also make sure to send us the currency of payments in cents.<br>
            <br>
            Learn more about the import here: <a class="simpleLink" href="/docs/data-import.html" target="_blank">Data Import</a>
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="project_id" value="Project" />
                <SelectInput
                    id="project_id"
                    v-model="createDataImportForm.project_id"
                    class="mt-1 block w-full"
                    :options="projects"
                />
                <InputError :message="createDataImportForm.errors.project_id" class="mt-2" />
            </div>

            <div v-if="createDataImportForm.project_id" class="col-span-6 sm:col-span-4">
                <InputLabel for="instance_id" value="Instance" />
                <SelectInput
                    id="instance_id"
                    v-model="createDataImportForm.instance_id"
                    class="mt-1 block w-full"
                    :options="instancesOfSelectedProject"
                />
                <InputError :message="createDataImportForm.errors.instance_id" class="mt-2" />
            </div>

            <div v-if="createDataImportForm.instance_id" class="col-span-6 sm:col-span-4">
                <InputLabel for="type" value="Import Type" />
                <SelectInput
                    id="type"
                    v-model="createDataImportForm.type"
                    class="mt-1 block w-full"
                    :options="importTypes"
                />
                <InputError :message="createDataImportForm.errors.type" class="mt-2" />
            </div>

            <div v-if="createDataImportForm.type" class="col-span-6 sm:col-span-4">
                <InputLabel for="file" value="Import File" />

                <FileInput
                    id="file"
                    @input="createDataImportForm.file = $event.target.files[0]"
                    class="mt-1 block w-full"
                />
                <InputError :message="createDataImportForm.errors.file" class="mt-2" />
            </div>
        </template>

        <template #actions>
            <div v-if="createDataImportForm.file">
                <ActionMessage :on="createDataImportForm.recentlySuccessful" class="me-3">
                    Started.
                </ActionMessage>

                <PrimaryButton v-tooltip="!permissions.canCreateDataImport ? 'You are not allowed to create new Data Imports due to plan or role limitations!' : ''"
                               :class="{ 'opacity-25': createDataImportForm.processing }"
                               :disabled="createDataImportForm.processing || !permissions.canCreateDataImport"
                               @click="confirmDataImportCreation"
                               type="button"
                >
                    Start Import
                </PrimaryButton>
            </div>
        </template>
    </FormSection>

    <!-- Delete Instance Confirmation Modal -->
    <ConfirmationModal :show="dataImportBeeingCreated" @close="dataImportBeeingCreated = false">
        <template #title>
            Create Data Import
        </template>

        <template #content>
            Do you really want to import this file with the following settings:<br>
            <br>
            <strong>Project:</strong> {{ projects.filter((project) => project.id == createDataImportForm.project_id)[0].name }}<br>
            <strong>Instance:</strong> {{ instances.filter((instance) => instance.id == createDataImportForm.instance_id)[0].name }}<br>
            <strong>Import Type:</strong> {{ createDataImportForm.type }}<br>
            <strong>Import File:</strong> {{ createDataImportForm.file.name }}<br>
        </template>

        <template #footer>
            <SecondaryButton @click="dataImportBeeingCreated = false">
                Cancel
            </SecondaryButton>

            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': createDataImportForm.processing }"
                :disabled="createDataImportForm.processing"
                @click="createDataImport"
            >
                Confirm Import
            </PrimaryButton>
        </template>
    </ConfirmationModal>
</template>

<style scoped>

</style>
