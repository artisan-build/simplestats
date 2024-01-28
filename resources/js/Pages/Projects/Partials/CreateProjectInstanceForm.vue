<script setup>

import InputError from "@/Components/InputError.vue";
import FormSection from "@/Components/FormSection.vue";
import TextInput from "@/Components/TextInput.vue";
import ActionMessage from "@/Components/ActionMessage.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {useForm} from "@inertiajs/vue3";

const props = defineProps({
    project: Object,
});

const createInstanceForm = useForm({
    name: '',
});

const createInstance = () => {
    createInstanceForm.post(route('projects.instances.store', props.project), {
        preserveScroll: true,
        onSuccess: () => {
            createInstanceForm.reset();
        },
    });
};

</script>

<template>
    <FormSection @submitted="createInstance">
        <template #title>
            Project Instances
        </template>

        <template #description>
            Manage or create new project-associated Instances.
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="name" value="Create New Instance" />
                <TextInput
                    id="name"
                    v-model="createInstanceForm.name"
                    type="text"
                    class="mt-1 block w-full"
                />
                <InputError :message="createInstanceForm.errors.name" class="mt-2" />
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
</template>

<style scoped>

</style>
