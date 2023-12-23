<script setup>
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    project: Object,
    permissions: Object,
});

const form = useForm({
    name: props.project.name,
});

const updateProjectName = () => {
    form.put(route('projects.update', props.project), {
        errorBag: 'updateProjectName',
        preserveScroll: true,
    });
};
</script>

<template>
    <FormSection @submitted="updateProjectName">
        <template #title>
            Project Name
        </template>

        <template #description>
            The project's name.
        </template>

        <template #form>
            <!-- Project Name -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="name" value="Project Name" />

                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="! permissions.canUpdateProject"
                />

                <InputError :message="form.errors.name" class="mt-2" />
            </div>
        </template>

        <template v-if="permissions.canUpdateProject" #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Saved.
            </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Save
            </PrimaryButton>
        </template>
    </FormSection>
</template>
