<script setup>
import {ref} from "vue";
import {router, useForm, usePage} from "@inertiajs/vue3";
import SelectInput from "@/Components/SelectInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import {periodSingular} from "@/Helpers/StringHelpers.js";
import pluralize from 'pluralize';
import MenuToggler from "@/Components/MenuToggler.vue";
import TextInput from "@/Components/TextInput.vue";
import DashboardPresetSwitcher from "@/Pages/Dashboard/Partials/DashboardPresetSwitcher.vue";
import CustomPresetModal from "@/Pages/Dashboard/Partials/CustomPresetModal.vue";
import ProjectFavicon from "@/Components/ProjectFavicon.vue";

const props = defineProps({
    teamStats: Object,
    statsGroups: Object,
    statsGroupNames: Object,
    presets: Array,
    allPresets: Array,
    hiddenPresets: Array,
    customPresets: Array,
    filters: Object,
    rangeTypes: Array,
    topReferers: Object,
    trackingUsageBits: Object,
    comparisonTypes: Object,
    submitRoute: {type: String, default: 'dashboard'},
    submitRouteParams: {type: Object, default: () => ({})},
});

const page = usePage();

const form = useForm(props.filters);

const rangeAmounts = Array.from({ length: 60 }, (_, i) => ({
    id: (i + 1).toString(),
    name: (i + 1).toString()
}));

const submit = () => {
    form.get(route(props.submitRoute, props.submitRouteParams), {preserveScroll: true});
};

const filterChanged = (event) => {
    // if a project changes, we need to reset some values, which may no longer relate to the new selected project
    if (event && event.name === 'project_id') {
        // reset group filters...
        for(let groupKey in props.statsGroups) {
            props.statsGroups[groupKey].forEach(item => {
                form[item.type] = 0;
            });
        }
        form.property_value = 0;
        form.campaign = 0;
    }

    if (form.preset && form.preset !== 'custom') {
        delete form.range_start;
        delete form.range_amount;
        delete form.range_type;
    }

    submit();
};

const periodRange = periodSingular[props.filters.range_type];

const chartDescription = `
    <div class="font-bold text-green-300">Current ${periodRange}</div>
    <div>
        As the current ${periodRange} may not be finished yet, the graphs could drastically go downwards compared to the previous ${periodRange}.
        The gray background on the charts is indicating that the current ${periodRange} is not finished yet!
    </div>
</div>
`;

const teamChanged = () => {
    router.put(route('current-team.update'), {
        team_id: page.props.auth.user.current_team_id,
    }, {
        preserveState: false,
    });
};

const isCustomRange = () => {
    return form.preset === 'custom';
};

const utmGroupTypes = ['track_source', 'track_medium', 'track_campaign', 'track_term', 'track_content'];

function groupLabel(group) {
    const base = pluralize.singular(group.title);

    return utmGroupTypes.includes(group.type) ? `UTM ${base}` : base;
}

function isStatsGroupSet() {
    return Boolean(props.filters.property_value) || Boolean(props.filters.campaign) || Object.values(props.statsGroups).some(statsGroup =>
        statsGroup.some(group => props.filters[group.type])
    );
}

const currentProject = props.teamStats.projects[props.filters.project_id] ?? props.teamStats.projects[0];

const showPresetModal = ref(false);
</script>

<template>
    <div>
        <div class="flex">
            <div>
                <MenuToggler />
            </div>
            <form @submit.prevent="submit" class="flex-grow">
                <div class="flex flex-wrap w-full gap-x-8 gap-y-5 items-center justify-start md:justify-end">
                    <div class="flex-grow">
                        <div class="flex flex-wrap gap-x-8 gap-y-5 items-center justify-between">
                            <div class="flex flex-wrap gap-x-2 items-center">
                                <div v-if="$page.props.auth.user.all_teams.length > 1">
                                    <InputLabel class="hidden" for="teamId" value="Team" />
                                    <SelectInput
                                        id="teamId"
                                        v-model="$page.props.auth.user.current_team_id"
                                        class="mt-1 block w-40 dark:!bg-dark-800"
                                        :options="$page.props.auth.user.all_teams"
                                        @change="teamChanged"
                                    />
                                </div>
                                <div>
                                    <InputLabel class="hidden" for="project_id" value="Project" />
                                    <SelectInput
                                        id="project_id"
                                        v-model="form.project_id"
                                        class="mt-1 block w-48 dark:!bg-dark-800"
                                        :options="teamStats.projects"
                                        @change="filterChanged"
                                    >
                                        <template #selected="{ option }">
                                            <span class="flex items-center gap-2">
                                                <ProjectFavicon :domain="option?.domain" />
                                                <span class="truncate">{{ option?.name ?? 'Select...' }}</span>
                                            </span>
                                        </template>
                                        <template #option="{ option }">
                                            <span class="flex items-center gap-2">
                                                <ProjectFavicon :domain="option.domain" />
                                                <span class="truncate">{{ option.name }}</span>
                                            </span>
                                        </template>
                                    </SelectInput>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-x-2 items-center">
                                <div v-if="isCustomRange()">
                                    <span>
                                        From
                                    </span>
                                </div>
                                <div v-if="isCustomRange()">
                                    <InputLabel class="hidden" for="range_start" value="Start from" />
                                    <TextInput
                                        type="date"
                                        id="range_start"
                                        v-model="form.range_start"
                                        class="mt-1 block w-36 dark:!bg-dark-800"
                                        @change="filterChanged"
                                    />
                                </div>
                                <div v-if="isCustomRange()">
                                    <span>
                                        last
                                    </span>
                                </div>
                                <div v-if="isCustomRange()">
                                    <InputLabel class="hidden" for="range_amount" value="Last" />
                                    <SelectInput
                                        id="range_amount"
                                        v-model="form.range_amount"
                                        class="mt-1 block w-20 dark:!bg-dark-800"
                                        :options="rangeAmounts"
                                        @change="filterChanged"
                                    />
                                </div>
                                <div v-if="isCustomRange()">
                                    <InputLabel class="hidden" for="range_type" value="&nbsp;" />
                                    <SelectInput
                                        id="range_type"
                                        v-model="form.range_type"
                                        class="mt-1 block w-28 dark:!bg-dark-800"
                                        :options="rangeTypes"
                                        @change="filterChanged"
                                    />
                                </div>
                                <div v-if="isCustomRange()">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-dot" viewBox="0 0 16 16">
                                        <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3"/>
                                    </svg>
                                </div>
                                <div class="flex items-center gap-1" data-tour="presets">
                                    <div>
                                        <InputLabel class="hidden" for="rangePreset" value="Preset" />
                                        <SelectInput
                                            id="rangePreset"
                                            v-model="form.preset"
                                            class="mt-1 block w-40 dark:!bg-dark-800"
                                            :options="presets"
                                            @change="filterChanged"
                                        />
                                    </div>
                                    <button type="button" @click="showPresetModal = true" title="Manage Presets" class="mt-1 p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded hover:bg-gray-100 dark:hover:bg-dark-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="hidden sm:block">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-dot" viewBox="0 0 16 16">
                                        <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3"/>
                                    </svg>
                                </div>
                                <div>
                                    <InputLabel class="hidden" for="rangeComparison" value="Comparison" />
                                    <SelectInput
                                        id="rangeComparison"
                                        v-model="form.comparison"
                                        class="mt-1 block w-full dark:!bg-dark-800"
                                        :options="comparisonTypes"
                                        @change="filterChanged"
                                    />
                                </div>
                                <div class="hidden sm:block">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-dot" viewBox="0 0 16 16">
                                        <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3"/>
                                    </svg>
                                </div>
                                <DashboardPresetSwitcher v-if="submitRoute !== 'retention'" :currentProject="currentProject" :trackingUsageBits="trackingUsageBits" />
                                <div class="hidden">
                                    <div v-tooltip.left="{ content: `<div class='flex flex-col space-y-4 max-w-64 p-2 text-sm'>${chartDescription}</div>`, html: true }" class="text-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="">
                                    <InputLabel class="hidden" for="refresh" value="&nbsp;" />
                                    <SecondaryButton type="submit" title="Reload" style="margin-top:3px;" class="py-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                        </svg>
                                        <span class="ml-2 hidden">Refresh</span>
                                    </SecondaryButton>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div v-if="isStatsGroupSet()" class="w-full bg-indigo-300 dark:bg-indigo-600 dark:text-dark-300 mt-3 p-1.5 rounded-lg">
            <div class="flex flex-wrap gap-2 items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <template v-for="statsGroup in statsGroups">
                    <template v-for="group in statsGroup">
                        <div v-if="filters[group.type]" class="bg-indigo-100 dark:bg-indigo-500 border rounded-md px-1 py-0.5">
                            {{ groupLabel(group) }} is <strong>{{ topReferers[statsGroupNames[group.type]] ?? statsGroupNames[group.type] }}</strong>
                            <button class="ml-1 hover:text-red-700" @click="(e) => { form[group.type] = 0; filterChanged(e); }">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 inline-block">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </template>
                <div v-if="filters.property_value" class="bg-indigo-100 dark:bg-indigo-500 border rounded-md px-1 py-0.5">
                    {{ statsGroupNames.property_value_title ?? 'Property' }} is <strong>{{ statsGroupNames.property_value }}</strong>
                    <button class="ml-1 hover:text-red-700" @click="(e) => { form.property_value = 0; filterChanged(e); }">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 inline-block">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div v-if="filters.campaign" class="bg-indigo-100 dark:bg-indigo-500 border rounded-md px-1 py-0.5">
                    Campaign is <strong>{{ statsGroupNames.campaign }}</strong>
                    <button class="ml-1 hover:text-red-700" @click="(e) => { form.campaign = 0; filterChanged(e); }">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 inline-block">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <CustomPresetModal
            :show="showPresetModal"
            :allPresets="allPresets"
            :hiddenPresets="hiddenPresets"
            :customPresets="customPresets"
            :rangeTypes="rangeTypes"
            @close="showPresetModal = false"
        />
    </div>
</template>
