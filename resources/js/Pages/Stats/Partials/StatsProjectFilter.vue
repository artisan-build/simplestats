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
    trackingSources: Array,
    trackingMedia: Array,
    trackingCampaigns: Array,
    trackingTerms: Array,
    trackingContents: Array,
});

const form = useForm(props.filters);

const rangeAmounts = Array.from({ length: 60 }, (_, i) => ({
    id: (i + 1).toString(),
    name: (i + 1).toString()
}));

const submit = () => {
    // the unsafe equality check is for reason...
    for (let key in form) {
        if (form.hasOwnProperty(key) && form[key] == '0') {
            delete form[key];
        }
    }

    form.get(route('stats.projects', props.project.id));
};

const fastSubmit = true;
</script>

<template>
    <form @submit.prevent="submit">
        <div class="px-4 py-5 bg-white dark:bg-gray-800 sm:p-6 shadow border rounded-md">
            <div class="flex gap-x-10 items-end">
                <div>
                    <InputLabel for="instanceId" value="Instances" />
                    <SelectInput
                        id="instanceId"
                        v-model="form.instanceId"
                        class="mt-1 block w-40"
                        :options="project.instances"
                        @change="fastSubmit ? submit() : null"
                    />
                </div>
                <div class="flex gap-x-2 items-end">
                    <div>
                        <InputLabel for="rangeAmount" value="Range" />
                        <SelectInput
                            id="rangeAmount"
                            v-model="form.rangeAmount"
                            class="mt-1 block w-18"
                            :options="rangeAmounts"
                            @change="fastSubmit ? submit() : null"
                        />
                    </div>
                    <div>
                        <SelectInput
                            id="rangeType"
                            v-model="form.rangeType"
                            class="mt-1 block w-28"
                            :options="rangeTypes"
                            @change="fastSubmit ? submit() : null"
                        />
                    </div>
                    <div>
                        <TextInput
                            type="date"
                            id="rangeStart"
                            v-model="form.rangeStart"
                            class="mt-1 block w-36"
                            @change="fastSubmit ? submit() : null"
                        />
                    </div>
                </div>
                <div class="flex gap-x-2 items-end">
                    <div v-if="trackingSources">
                        <InputLabel for="trackSource" value="Source" />
                        <SelectInput
                            id="trackSource"
                            v-model="form.trackSource"
                            class="mt-1 block w-36"
                            :options="trackingSources"
                            @change="fastSubmit ? submit() : $inertia.reload({
                                only: ['trackingMedia']
                            })"
                        />
                    </div>
                    <div v-if="trackingMedia">
                        <InputLabel for="trackMedium" value="Medium" />
                        <SelectInput
                            id="trackMedium"
                            v-model="form.trackMedium"
                            class="mt-1 block w-36"
                            :options="trackingMedia"
                            @change="fastSubmit ? submit() : $inertia.reload({
                                only: ['trackingCampaigns']
                            })"
                        />
                    </div>
                    <div v-if="trackingCampaigns">
                        <InputLabel for="trackCampaign" value="Campaign" />
                        <SelectInput
                            id="trackCampaign"
                            v-model="form.trackCampaign"
                            class="mt-1 block w-36"
                            :options="trackingCampaigns"
                            @change="fastSubmit ? submit() : $inertia.reload({
                                only: ['trackingTerms']
                            })"
                        />
                    </div>
                    <div v-if="trackingTerms">
                        <InputLabel for="trackTerm" value="Term" />
                        <SelectInput
                            id="trackTerm"
                            v-model="form.trackTerm"
                            class="mt-1 block w-36"
                            :options="trackingTerms"
                            @change="fastSubmit ? submit() : $inertia.reload({
                                only: ['trackingContents']
                            })"
                        />
                    </div>
                    <div v-if="trackingContents">
                        <InputLabel for="trackContent" value="Content" />
                        <SelectInput
                            id="trackContent"
                            v-model="form.trackContent"
                            class="mt-1 block w-36"
                            :options="trackingContents"
                            @change="fastSubmit ? submit() : null"
                        />
                    </div>
                </div>
                <div>
                    <PrimaryButton v-if="!fastSubmit" class="h-10" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Submit
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </form>
</template>
