<script setup>
import {useForm} from "@inertiajs/vue3";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import TrackingUsageSelector from "@/Components/TrackingUsageSelector.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import FullscreenCard from "@/Components/FullscreenCard.vue";

const props = defineProps({
    project: Object,
    trackingUsages: Object,
});

const form = useForm({
    name: props.project.name || '',
    domain: props.project.domain || '',
    usage: props.project.usage || 0,
});

const submit = () => {
    form.post(route('setup.project.store'));
};

const action = (props.project.name) ? 'Update Project' : 'Create Project';
</script>

<template>
    <FullscreenCard :title="action" description="Provide your project details.">
        <form @submit.prevent="submit">
            <div class="mb-6">
                <label for="project" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Name
                </label>
                <TextInput
                    id="project"
                    v-model="form.name"
                    type="text"
                    class="block w-full dark:bg-dark-950"
                    placeholder="My great SaaS"
                />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <div class="mb-6">
                <label for="domain" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Domain
                </label>
                <TextInput
                    id="domain"
                    v-model="form.domain"
                    type="text"
                    class="block w-full dark:bg-dark-950"
                    placeholder="example.com"
                />
                <InputError :message="form.errors.domain" class="mt-2" />
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    What would you like to track?
                </label>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                    Select all options that apply to your use case.
                </p>
                <div class="w-full">
                    <TrackingUsageSelector v-model="form.usage" :trackingUsages="trackingUsages" :stacked="true" />
                    <div class="text-sm mt-2 text-gray-500 dark:text-gray-400">
                        In most cases, it's best to select all...
                    </div>
                </div>
                <InputError :message="form.errors.usage" class="mt-2" />
            </div>

            <div class="mt-8">
                <PrimaryButton class="w-full !text-sm justify-center !p-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    {{ action }}
                </PrimaryButton>
            </div>
        </form>
    </FullscreenCard>
</template>
