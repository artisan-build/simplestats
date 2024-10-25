<script setup>
import {router, useForm, usePage} from "@inertiajs/vue3";
import SelectInput from "@/Components/SelectInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import {periodSingular} from "@/Helpers/StringHelpers.js";
import pluralize from 'pluralize';
import MenuToggler from "@/Components/MenuToggler.vue";
import EnvBadge from "@/Components/EnvBadge.vue";
import {
    addHours,
    differenceInDays, differenceInHours,
    differenceInMonths, differenceInYears,
    endOfMonth, endOfYear,
    format, startOfDay, startOfHour,
    startOfMonth,
    startOfYear,
    subDays,
    subMonths, subYears
} from "date-fns";
import TextInput from "@/Components/TextInput.vue";
import {onMounted, ref} from "vue";
import ToggleButton from "@/Components/ToggleButton.vue";

const props = defineProps({
    teamStats: Object,
    statsGroups: Object,
    statsGroupNames: Object,
    filters: Object,
    rangeTypes: Array,
    topReferers: Object,
    trackingUsageBits: Object,
});

const maxTrackingUsageBits = Object.values(props.trackingUsageBits).reduce((a, b) => a + b);

const page = usePage();

const form = useForm(props.filters);

const rangeAmounts = Array.from({ length: 60 }, (_, i) => ({
    id: (i + 1).toString(),
    name: (i + 1).toString()
}));

const submit = () => {
    form.get(route('dashboard'), {preserveScroll: true});
};

const filterChanged = (event) => {
    // if a project changes, we need to reset some values, which may no longer relate to the new selected project
    if (event && event.target.id === 'projectId') {
        form.instanceId = 0;

        // reset group filters...
        for(let groupKey in props.statsGroups) {
            props.statsGroups[groupKey].forEach(item => {
                form[item.type] = 0;
            });
        }
    }

    submit();
};

const periodRange = periodSingular[props.filters.rangeType];

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

let allTimeType = 'months';
let allTimeRange = differenceInMonths(new Date(), page.props.statsStartingAt ?? startOfYear(subYears(new Date(), 1)));

// wenn all time range ist größer als 4 jahre, dann range type auf years stellen
if (allTimeRange > (12 * 4)) {
    allTimeType = 'years';
    allTimeRange = differenceInYears(new Date(), page.props.statsStartingAt ?? startOfYear(subYears(new Date(), 1)));
}

const presets = [
    {name: 'Today', rangeStart: format(startOfDay(new Date()), 'y-MM-dd'), rangeAmount: differenceInHours(startOfHour(addHours(new Date(), 1)), startOfDay(new Date())), rangeType: 'hours'},
    {name: 'Yesterday', rangeStart: format(startOfDay(subDays(new Date(), 1)), 'y-MM-dd'), rangeAmount: 24, rangeType: 'hours'},
    {name: 'Last 7 Days', rangeStart: format(new Date(), 'y-MM-dd'), rangeAmount: 7, rangeType: 'days'},
    {name: 'Last 30 Days', rangeStart: format(new Date(), 'y-MM-dd'), rangeAmount: 30, rangeType: 'days'},
    // {name: 'Last 12 Months', rangeStart: format(new Date(), 'y-MM-dd'), rangeAmount: differenceInMonths(new Date(), startOfMonth(subMonths(new Date(), 12))), rangeType: 'months'},
    {name: 'This Month', rangeStart: format(new Date(), 'y-MM-dd'), rangeAmount: differenceInDays(new Date(), startOfMonth(new Date())) + 1, rangeType: 'days'},
    {name: 'Last Month', rangeStart: format(endOfMonth(subMonths(new Date(), 1)), 'y-MM-dd'), rangeAmount: differenceInDays(endOfMonth(subMonths(new Date(), 1)), startOfMonth(subMonths(new Date(), 1))) + 1, rangeType: 'days'},
    {name: 'This Year', rangeStart: format(new Date(), 'y-MM-dd'), rangeAmount: differenceInMonths(new Date(), startOfYear(new Date())) + 1, rangeType: 'months'},
    {name: 'Last Year', rangeStart: format(endOfYear(subYears(new Date(), 1)), 'y-MM-dd'), rangeAmount: differenceInMonths(endOfYear(subYears(new Date(), 1)), startOfYear(subYears(new Date(), 1))) + 1, rangeType: 'months'},
    {name: 'All Time', rangeStart: format(new Date(), 'y-MM-dd'), rangeAmount: allTimeRange + 2, rangeType: allTimeType},
    {name: 'Custom', rangeStart: '', rangeAmount: 0, rangeType: ''},
].map((preset, index) => ({ ...preset, id: index }));

const presetSelected = ref(null);
const customRangeId = presets.length-1;

onMounted(() => {
    presets.some(function (preset) {
        if (preset.rangeStart === form.rangeStart && preset.rangeAmount === form.rangeAmount && preset.rangeType === form.rangeType) {
            presetSelected.value = preset.id;
            return true;
        }
    });

    if (presetSelected.value === null) {
        presetSelected.value = customRangeId;
    }
});

function isCustomRange() {
    return presetSelected.value == customRangeId;
}

const presetChanged = (event) => {
    const selectedPreset = presets[presetSelected.value];

    if (isCustomRange()) {
        return;
    }

    form.rangeStart = selectedPreset.rangeStart;
    form.rangeAmount = selectedPreset.rangeAmount;
    form.rangeType = selectedPreset.rangeType;

    filterChanged(event);
};

function isStatsGroupSet() {
    return Object.values(props.statsGroups).some(statsGroup =>
        statsGroup.some(group => props.filters[group.type])
    );
}

const currentProject = props.teamStats.projects[props.filters.projectId];
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
                                        class="mt-1 block w-40"
                                        :options="$page.props.auth.user.all_teams"
                                        @change="teamChanged"
                                    />
                                </div>
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
                                <div v-if="currentProject.instances && teamStats.totalInstancesCount > 1">
                                    <InputLabel class="hidden" for="instanceId" value="Instance" />
                                    <SelectInput
                                        id="instanceId"
                                        v-model="form.instanceId"
                                        class="mt-1 block w-40"
                                        :options="currentProject.instances"
                                        @change="filterChanged"
                                    />
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-x-2 items-center">
                                <div v-if="isCustomRange()">
                                    <span>
                                        From
                                    </span>
                                </div>
                                <div v-if="isCustomRange()">
                                    <InputLabel class="hidden" for="rangeStart" value="Start from" />
                                    <TextInput
                                        type="date"
                                        id="rangeStart"
                                        v-model="form.rangeStart"
                                        class="mt-1 block w-36"
                                        @change="filterChanged"
                                    />
                                </div>
                                <div v-if="isCustomRange()">
                                    <span>
                                        last
                                    </span>
                                </div>
                                <div v-if="isCustomRange()">
                                    <InputLabel class="hidden" for="rangeAmount" value="Last" />
                                    <SelectInput
                                        id="rangeAmount"
                                        v-model="form.rangeAmount"
                                        class="mt-1 block w-18"
                                        :options="rangeAmounts"
                                        @change="filterChanged"
                                    />
                                </div>
                                <div v-if="isCustomRange()">
                                    <InputLabel class="hidden" for="rangeType" value="&nbsp;" />
                                    <SelectInput
                                        id="rangeType"
                                        v-model="form.rangeType"
                                        class="mt-1 block w-28"
                                        :options="rangeTypes"
                                        @change="filterChanged"
                                    />
                                </div>
                                <div v-if="isCustomRange()">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-dot" viewBox="0 0 16 16">
                                        <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3"/>
                                    </svg>
                                </div>
                                <div>
                                    <InputLabel class="hidden" for="rangePreset" value="Preset" />
                                    <SelectInput
                                        id="rangePreset"
                                        v-model="presetSelected"
                                        class="mt-1 block w-18"
                                        :options="presets"
                                        @change="presetChanged"
                                    />
                                </div>
                                <div v-if="maxTrackingUsageBits === currentProject.usage" class="pl-3">
                                    <InputLabel class="hidden" for="advanced" value="&nbsp;" />
                                    <ToggleButton class="mt-1" id="advanced" title="Toggle Advanced View" v-model:checked="page.props.auth.user.settings.showAdvanced" @click="$inertia.get(route('user-settings.toggle-advanced'))">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 16.875h3.375m0 0h3.375m-3.375 0V13.5m0 3.375v3.375M6 10.5h2.25a2.25 2.25 0 0 0 2.25-2.25V6a2.25 2.25 0 0 0-2.25-2.25H6A2.25 2.25 0 0 0 3.75 6v2.25A2.25 2.25 0 0 0 6 10.5Zm0 9.75h2.25A2.25 2.25 0 0 0 10.5 18v-2.25a2.25 2.25 0 0 0-2.25-2.25H6a2.25 2.25 0 0 0-2.25 2.25V18A2.25 2.25 0 0 0 6 20.25Zm9.75-9.75H18a2.25 2.25 0 0 0 2.25-2.25V6A2.25 2.25 0 0 0 18 3.75h-2.25A2.25 2.25 0 0 0 13.5 6v2.25a2.25 2.25 0 0 0 2.25 2.25Z" />
                                        </svg>
                                    </ToggleButton>
                                </div>
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
                                <EnvBadge />
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div v-if="isStatsGroupSet()" class="w-full bg-indigo-300 mt-3 p-1.5 rounded-lg">
            <div class="flex flex-wrap gap-2 items-center">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <template v-for="statsGroup in statsGroups">
                    <template v-for="group in statsGroup">
                        <div v-if="filters[group.type]" class="bg-indigo-100 border rounded-md px-1 py-0.5">
                            {{ pluralize.singular(group.title) }} is <strong>{{ topReferers[statsGroupNames[group.type]] ?? statsGroupNames[group.type] }}</strong>
                            <button class="ml-1 hover:text-red-700" @click="(e) => { form[group.type] = 0; filterChanged(e); }">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 inline-block">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </template>
            </div>
        </div>
    </div>
</template>
