<script setup>
import {useForm} from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SelectInput from "@/Components/SelectInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import {Link} from "@inertiajs/vue3";

const props = defineProps({
    teamStats: Object,
    filters: Object,
    rangeTypes: Array,
    trackAllKey: Number,
    trackingSources: Array,
    trackingMedia: Array,
    trackingCampaigns: Array,
    trackingTerms: Array,
    trackingContents: Array,
});

const form = useForm(props.filters);

const rangeAmounts = Array.from({ length: 60 - 2 }, (_, i) => ({
    // start from min 3 days cause smaller would break the charts, and it not really makes sense...
    id: (i + 3).toString(),
    name: (i + 3).toString()
}));

const submit = () => {
    form.get(route('dashboard'));
};

const trackFields = ['trackSource', 'trackMedium', 'trackCampaign', 'trackTerm', 'trackContent'];

const checkHierachicalTracking = (trackId) => {
    let startIndex = trackFields.indexOf(trackId);

    if (startIndex !== -1 && form[trackId] <= props.trackAllKey) {
        for (let i = startIndex + 1; i < trackFields.length; i++) {
            form[trackFields[i]] = props.trackAllKey;
        }
    }
};

const filterChanged = (event) => {
    if (event.target.id.startsWith('track')) {
        checkHierachicalTracking(event.target.id);
    }

    // if a project changes, we need to reset some values, which may no longer relate to the new selected project
    if (event.target.id === 'projectId') {
        form.instanceId = props.trackAllKey;
        trackFields.forEach(function(trackField) {
            form[trackField] = props.trackAllKey;
        });
    }

    // the unsafe equality check is for reason...
    for (let key in form) {
        if (form.hasOwnProperty(key) && form[key] == props.trackAllKey) {
            delete form[key];
        }
    }

    submit();
};

</script>

<template>
    <form @submit.prevent="submit">
        <div>
            <div class="flex flex-wrap w-full gap-x-8 gap-y-5 items-center">
                <div class="flex flex-wrap gap-x-8 gap-y-5 items-center">
                    <div class="flex flex-wrap gap-x-2 items-center">
                        <div>
                            <InputLabel class="hidden" for="projectId" value="Project" />
                            <SelectInput
                                id="projectId"
                                v-model="form.projectId"
                                class="mt-1 block w-40"
                                :options="teamStats.projects"
                                @change="filterChanged"
                            />
                        </div>
                        <div v-if="teamStats.projects[filters.projectId].instances && teamStats.totalInstancesCount > 0">
                            <InputLabel class="hidden" for="instanceId" value="Instance" />
                            <SelectInput
                                id="instanceId"
                                v-model="form.instanceId"
                                class="mt-1 block w-40"
                                :options="teamStats.projects[filters.projectId].instances"
                                @change="filterChanged"
                            />
                        </div>
                        <div v-if="teamStats.totalProjectsCount >= 1 && teamStats.totalInstancesCount === 0">
                            <InputLabel class="hidden" for="instanceId" value="Instance" />
                            <PrimaryButton class="h-10" type="button" @click="$inertia.get(route('projects.show', Object.values(teamStats.projects)[1].id))">
                                Create first Instance
                            </PrimaryButton>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-x-2 items-center">
                        <div>
                            <InputLabel class="hidden" for="rangeAmount" value="Range" />
                            <SelectInput
                                id="rangeAmount"
                                v-model="form.rangeAmount"
                                class="mt-1 block w-18"
                                :options="rangeAmounts"
                                @change="filterChanged"
                            />
                        </div>
                        <div>
                            <SelectInput
                                id="rangeType"
                                v-model="form.rangeType"
                                class="mt-1 block w-28"
                                :options="rangeTypes"
                                @change="filterChanged"
                            />
                        </div>
                        <div>
                            <TextInput
                                type="date"
                                id="rangeStart"
                                v-model="form.rangeStart"
                                class="mt-1 block w-36"
                                @change="filterChanged"
                            />
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-x-8 gap-y-5 items-center">
                    <div v-if="filters.projectId" class="flex flex-wrap gap-x-2 items-center">
                        <div v-if="trackingSources" class="text-base font-medium text-gray-600 pt-1">
                            UTMs:
                        </div>
                        <div v-if="trackingSources">
                            <InputLabel class="hidden" for="trackSource" value="Source" />
                            <SelectInput
                                id="trackSource"
                                v-model="form.trackSource"
                                class="mt-1 block w-36"
                                :options="trackingSources"
                                @change="filterChanged"
                            />
                        </div>
                        <div v-if="trackingMedia">
                            <InputLabel class="hidden" for="trackMedium" value="Medium" />
                            <SelectInput
                                id="trackMedium"
                                v-model="form.trackMedium"
                                class="mt-1 block w-36"
                                :options="trackingMedia"
                                @change="filterChanged"
                            />
                        </div>
                        <div v-if="trackingCampaigns">
                            <InputLabel class="hidden" for="trackCampaign" value="Campaign" />
                            <SelectInput
                                id="trackCampaign"
                                v-model="form.trackCampaign"
                                class="mt-1 block w-36"
                                :options="trackingCampaigns"
                                @change="filterChanged"
                            />
                        </div>
                        <div v-if="trackingTerms">
                            <InputLabel class="hidden" for="trackTerm" value="Term" />
                            <SelectInput
                                id="trackTerm"
                                v-model="form.trackTerm"
                                class="mt-1 block w-36"
                                :options="trackingTerms"
                                @change="filterChanged"
                            />
                        </div>
                        <div v-if="trackingContents">
                            <InputLabel class="hidden" for="trackContent" value="Content" />
                            <SelectInput
                                id="trackContent"
                                v-model="form.trackContent"
                                class="mt-1 block w-36"
                                :options="trackingContents"
                                @change="filterChanged"
                            />
                        </div>
                    </div>
                    <div class="">
                        <SecondaryButton type="submit" class="py-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            <span class="ml-2 hidden">Refresh</span>
                        </SecondaryButton>
                    </div>
                </div>
            </div>
        </div>
    </form>
</template>
