<script setup>
import {useForm} from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SelectInput from "@/Components/SelectInput.vue";

const props = defineProps({
    project: Object,
    filters: Object,
});

const form = useForm({
    instance_id: props.filters.instance_id,
    range_type: props.filters.range_type,
    range_amount: props.filters.range_amount,
    range_start: props.filters.range_start,
});

const submit = () => {
    if (form.instance_id === '0') {
        delete form.instance_id;
    }

    form.get(route('stats.projects', props.project.id));
};
</script>

<template>
    <form @submit.prevent="submit">
        <div class="px-4 py-5 bg-white dark:bg-gray-800 sm:p-6 shadow sm:rounded-tl-md sm:rounded-tr-md">
            {{ project.name }}
            <SelectInput
                id="instance_id"
                v-model="form.instance_id"
                class="mt-1 block"
                :options="project.instances"
            />
        </div>

        <div class="flex items-center justify-end px-4 py-3 bg-gray-50 dark:bg-gray-800 text-end sm:px-6 shadow sm:rounded-bl-md sm:rounded-br-md">
            <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Submit
            </PrimaryButton>
        </div>
    </form>
</template>
