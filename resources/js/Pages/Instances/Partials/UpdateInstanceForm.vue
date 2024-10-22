<script setup>
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from "@/Components/SelectInput.vue";

const props = defineProps({
    instance: Object,
    projects: Array,
    permissions: Object,
});

const form = useForm({
    name: props.instance.name,
    project_id: props.instance.project_id,
});

const updateInstance = () => {
    form.put(route('projects.instances.update', [props.instance.project_id, props.instance.id]), {
        errorBag: 'updateInstance',
        preserveScroll: true,
    });
};
</script>

<template>
    <FormSection @submitted="updateInstance">
        <template #title>
            Instance
        </template>

        <template #description>
            Information about the instance.
        </template>

        <template #form>
            <!-- Instance Name -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="name" value="Instance name" />

                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="! permissions.canUpdateInstance"
                />

                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="project_id" value="Instance project" />

                <SelectInput
                    id="project_id"
                    v-model="form.project_id"
                    class="mt-1 block w-full"
                    :disabled="! permissions.canUpdateInstance"
                    :options="projects"
                />

                <InputError :message="form.errors.project_id" class="mt-2" />
            </div>
        </template>

        <template v-if="permissions.canUpdateInstance" #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Saved.
            </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Save
            </PrimaryButton>
        </template>
    </FormSection>
</template>
