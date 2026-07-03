<script setup>
import {useForm} from "@inertiajs/vue3";
import SelectInput from "@/Components/SelectInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import ScopeSelector from "@/Components/ScopeSelector.vue";
import DotIcon from "bootstrap-icons/icons/dot.svg?component";

const props = defineProps({
    teamStats: Object,
    filters: Object,
    presets: Array,
    rangeTypes: Array,
    submitRoute: {type: String, default: 'campaigns'},
    submitRouteParams: {type: Object, default: () => ({})},
});

const form = useForm(props.filters);

const rangeAmounts = Array.from({length: 60}, (_, i) => ({
    id: (i + 1).toString(),
    name: (i + 1).toString(),
}));

const isCustomRange = () => form.preset === 'custom';

const submit = () => {
    form.get(route(props.submitRoute, props.submitRouteParams), {preserveScroll: true});
};

const filterChanged = () => {
    if (form.preset && form.preset !== 'custom') {
        delete form.range_start;
        delete form.range_amount;
        delete form.range_type;
    }

    submit();
};

</script>

<template>
    <div class="flex items-center gap-x-2">
        <form @submit.prevent="submit" class="flex flex-wrap gap-x-3 gap-y-3 items-center">
            <ScopeSelector
                v-model:project-id="form.project_id"
                :projects="teamStats.projects"
                @project-changed="filterChanged"
            />

            <div class="flex flex-wrap gap-x-2 items-center">
                <template v-if="isCustomRange()">
                    <span class="text-sm text-gray-500 dark:text-dark-400">From</span>
                    <InputLabel class="hidden" for="range_start" value="Start from" />
                    <TextInput type="date" id="range_start" v-model="form.range_start" class="block w-36 dark:!bg-dark-800" @change="filterChanged" />
                    <span class="text-sm text-gray-500 dark:text-dark-400">last</span>
                    <InputLabel class="hidden" for="range_amount" value="Last" />
                    <SelectInput id="range_amount" v-model="form.range_amount" class="block w-20 dark:!bg-dark-800" :options="rangeAmounts" @change="filterChanged" />
                    <InputLabel class="hidden" for="range_type" value="&nbsp;" />
                    <SelectInput id="range_type" v-model="form.range_type" class="block w-28 dark:!bg-dark-800" :options="rangeTypes" @change="filterChanged" />
                    <DotIcon class="size-4 text-gray-400" />
                </template>

                <InputLabel class="hidden" for="rangePreset" value="Preset" />
                <SelectInput
                    id="rangePreset"
                    v-model="form.preset"
                    class="block w-40 dark:!bg-dark-800"
                    :options="presets"
                    @change="filterChanged"
                />
            </div>
        </form>
    </div>
</template>
