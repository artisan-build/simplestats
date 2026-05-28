<script setup>
import {useForm} from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import TrackingUsageSelector from "@/Components/TrackingUsageSelector.vue";

const props = defineProps({
    project: Object,
    permissions: Object,
    trackingUsages: Object,
});

const form = useForm({
    name: props.project.name,
    domain: props.project.domain,
    usage: props.project.usage,
});

const updateProject = () => {
    form.put(route('projects.update', props.project), {
        errorBag: 'updateProject',
        preserveScroll: true,
    });
};

const appName = import.meta.env.VITE_APP_NAME;
</script>

<template>
    <FormSection @submitted="updateProject">
        <template #title>
            Project
        </template>

        <template #description>
            Details about the project.
        </template>

        <template #form>
            <!-- Project Name -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="! permissions.canUpdateProject"
                />

                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="domain" value="Domain" />

                <TextInput
                    id="domain"
                    v-model="form.domain"
                    type="text"
                    class="mt-1 block w-full"
                    placeholder="example.com"
                    :disabled="! permissions.canUpdateProject"
                />

                <InputError :message="form.errors.domain" class="mt-2" />
            </div>

            <div class="col-span-6">
                <InputLabel for="usage" :value="`What would you like to track?`" />
                <div class="mt-1">
                    <TrackingUsageSelector v-model="form.usage" :trackingUsages="trackingUsages" />
                </div>
                <InputError :message="form.errors.usage" class="mt-2" />
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
