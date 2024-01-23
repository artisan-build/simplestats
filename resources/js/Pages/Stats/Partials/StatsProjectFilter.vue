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
    tracking: Object,
});

const form = useForm(props.filters);

const rangeAmounts = Array.from({ length: 60 }, (_, i) => ({
    id: (i + 1).toString(),
    name: (i + 1).toString()
}));

const submit = () => {
    // the unsafe equality check is for reason...
    if (form.instance_id == '0') {delete form.instance_id;}
    if (form.track.source == '0') {delete form.track.source;}
    if (form.track.medium == '0') {delete form.track.medium;}

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
                            v-model="form.range.amount"
                            class="mt-1 block"
                            :options="rangeAmounts"
                        />
                    </div>
                    <div>
                        <SelectInput
                            id="range_type"
                            v-model="form.range.type"
                            class="mt-1 block"
                            :options="rangeTypes"
                        />
                    </div>
                    <div>
                        <TextInput
                            type="date"
                            id="range_start"
                            v-model="form.range.start"
                            class="mt-1 block"
                        />
                    </div>
                </div>
                <div class="flex gap-x-2 items-end">
                    <div v-if="tracking && tracking.source">
                        <InputLabel for="track_source" value="Source" />
                        <SelectInput
                            id="track_source"
                            v-model="form.track.source"
                            class="mt-1 block"
                            :options="tracking.source"
                        />
                    </div>
                    <div v-if="tracking && tracking.medium">
                        <InputLabel for="track_medium" value="Medium" />
                        <SelectInput
                            id="track_medium"
                            v-model="form.track.medium"
                            class="mt-1 block"
                            :options="tracking.medium"
                        />
                    </div>
                </div>
                <div>
                    <PrimaryButton class="h-10" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Submit
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </form>
</template>
