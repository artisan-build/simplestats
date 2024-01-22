<script setup>
import {useForm} from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SelectInput from "@/Components/SelectInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({
    project: Object,
    filters: Object,
    rangeTypes: Array,
});

const form = useForm({
    instance_id: props.filters.instance_id,
    range_type: props.filters.range_type,
    range_amount: props.filters.range_amount,
    range_start: props.filters.range_start,
});

const rangeAmounts = Array.from({ length: 60 }, (_, i) => ({
    id: (i + 1).toString(),
    name: (i + 1).toString()
}));

const submit = () => {
    if (form.instance_id == '0') {
        delete form.instance_id;
    }

    form.get(route('stats.projects', props.project.id));
};
</script>

<template>
    <form @submit.prevent="submit">
        <div class="px-4 py-5 bg-white dark:bg-gray-800 sm:p-6 shadow border rounded-md">
            <div class="flex gap-x-10 items-end">
                <div>
                    <InputLabel for="instance_id" value="Instances" />
                    <SelectInput
                        id="instance_id"
                        v-model="form.instance_id"
                        class="mt-1 block"
                        :options="project.instances"
                    />
                </div>
                <div class="flex gap-x-2 items-end">
                    <div>
                        <InputLabel for="range_amount" value="Range" />
                        <SelectInput
                            id="range_amount"
                            v-model="form.range_amount"
                            class="mt-1 block"
                            :options="rangeAmounts"
                        />
                    </div>
                    <div>
                        <InputLabel for="range_type" value="&nbsp;" />
                        <SelectInput
                            id="range_type"
                            v-model="form.range_type"
                            class="mt-1 block"
                            :options="rangeTypes"
                        />
                    </div>
                    <div>
                        <InputLabel for="range_start" value="&nbsp;" />
                        <TextInput
                            type="date"
                            id="range_start"
                            v-model="form.range_start"
                            class="mt-1 block"
                        />
                    </div>
                </div>
                <div>
                    <InputLabel for="action" value="&nbsp;" />
                    <PrimaryButton class="h-10" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Submit
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </form>
</template>
