<script setup>
import {useForm} from "@inertiajs/vue3";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import UsageField from "@/Components/Project/UsageField.vue";
import FlagsField from "@/Components/Project/FlagsField.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import FullscreenCard from "@/Components/FullscreenCard.vue";

const props = defineProps({
    project: Object,
    usages: Object,
    flags: Object,
});

const form = useForm({
    name: props.project.name || '',
    domain: props.project.domain || '',
    usage: props.project.usage || 0,
    flags: props.project.flags || 0,
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

            <UsageField
                v-model="form.usage"
                :usages="usages"
                :error="form.errors.usage"
                :stacked="true"
            />

            <FlagsField
                v-model="form.flags"
                :flags="flags"
                :usage="form.usage"
                :usages="usages"
                :error="form.errors.flags"
                :stacked="true"
            />

            <div class="mt-8">
                <PrimaryButton class="w-full !text-sm justify-center !p-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    {{ action }}
                </PrimaryButton>
            </div>
        </form>
    </FullscreenCard>
</template>
