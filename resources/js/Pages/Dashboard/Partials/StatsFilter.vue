<script setup>
import {computed, nextTick, ref, watch} from "vue";
import {Link, useForm} from "@inertiajs/vue3";
import SelectInput from "@/Components/SelectInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import {periodSingular} from "@/Helpers/StringHelpers.js";
import pluralize from 'pluralize';
import CustomPresetModal from "@/Pages/Dashboard/Partials/CustomPresetModal.vue";
import CustomRangePicker from "@/Pages/Dashboard/Partials/CustomRangePicker.vue";
import CohortPicker from "@/Pages/Dashboard/Partials/CohortPicker.vue";
import ScopeSelector from "@/Components/ScopeSelector.vue";
import InfoTooltip from "@/Components/InfoTooltip.vue";
import {useUsage} from "@/Composables/useUsage.js";
import {useAiChat} from "@/Composables/useAiChat.js";
import DotIcon from 'bootstrap-icons/icons/dot.svg?component';
import GearIcon from 'bootstrap-icons/icons/gear.svg?component';
import InfoIcon from 'bootstrap-icons/icons/info-circle.svg?component';
import RefreshIcon from 'bootstrap-icons/icons/arrow-repeat.svg?component';
import SearchIcon from 'bootstrap-icons/icons/search.svg?component';
import CloseIcon from 'bootstrap-icons/icons/x-lg.svg?component';
import StarsIcon from 'bootstrap-icons/icons/stars.svg?component';

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
    usageBits: Object,
    comparisonTypes: Object,
    submitRoute: {type: String, default: 'dashboard'},
    submitRouteParams: {type: Object, default: () => ({})},
});

// Inertia's useForm only serializes keys present at init. The cohort fields are
// dropped from props.filters when inactive, so they must be seeded explicitly,
// otherwise selecting a cohort would never be sent with the request.
const form = useForm({
    ...props.filters,
    cohort_start: props.filters.cohort_start ?? null,
    cohort_end: props.filters.cohort_end ?? null,
    cohort_label: props.filters.cohort_label ?? null,
});

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
        form.cohort_start = null;
        form.cohort_end = null;
        form.cohort_label = null;
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

const isCustomRange = () => {
    return form.preset === 'custom';
};

const customRangePicker = ref(null);

// Switching to "Custom" only reveals the opened picker; the request is
// deferred until the range is applied, so the dashboard doesn't reload twice.
// The open() must wait until the closing preset listbox has restored focus to
// its own button, otherwise the popover closes again (focus moved outside).
const presetChanged = (e) => {
    if (form.preset === 'custom') {
        nextTick(() => setTimeout(() => customRangePicker.value?.open(), 150));
        return;
    }

    filterChanged(e);
};

const applyCustomRange = (payload) => {
    form.range_start = payload.range_start;
    form.range_amount = payload.range_amount;
    form.range_type = payload.range_type;
    submit();
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

const clearCohort = (e) => {
    form.cohort_start = null;
    form.cohort_end = null;
    form.cohort_label = null;
    filterChanged(e);
};

// The cohort window only narrows the users; the observation period stays
// whatever is selected separately.
const applyCohort = (payload) => {
    form.cohort_start = payload.cohort_start;
    form.cohort_end = payload.cohort_end;
    form.cohort_label = payload.cohort_label;
    submit();
};

const currentProject = props.teamStats.projects[props.filters.project_id] ?? props.teamStats.projects[0];

const {currentProjectHasUsages} = useUsage(currentProject, props.usageBits);
const showCohortPicker = props.submitRoute !== 'retention' && currentProjectHasUsages(['users']);

// period vs. cycle isn't self-evident from the labels, so spell out what each one
// compares against (kept generic across range types).
const comparisonHelp = `
    <div class="space-y-1.5 text-left">
        <div><span class="font-semibold text-green-300">Previous period</span><br>The equally long span right before the current one (e.g. the 12 weeks before the last 12).</div>
        <div><span class="font-semibold text-green-300">Previous cycle</span><br>The matching slot one calendar step up: a day vs. the same weekday last week, a week vs. last month, a month vs. last year. Good for weekday or seasonal patterns.</div>
        <div><span class="font-semibold text-green-300">Year over year</span><br>The exact same range one year earlier.</div>
    </div>
`;

const comparisonActive = computed(() => Boolean(form.comparison) && String(form.comparison) !== '0');
const comparisonClass = computed(() => comparisonActive.value
    ? 'mt-1 block w-full !bg-indigo-100 dark:!bg-indigo-500 !border-indigo-300 dark:!border-indigo-600'
    : 'mt-1 block w-full dark:!bg-dark-800');

const resetFilters = (e) => {
    form.property_value = 0;
    form.campaign = 0;

    Object.values(props.statsGroups).forEach(statsGroup =>
        statsGroup.forEach(item => {
            form[item.type] = 0;
        })
    );

    filterChanged(e);
};

const showPresetModal = ref(false);

const aiChat = useAiChat();

// The assistant defaults to the project the user is currently looking at.
watch(() => form.project_id, (id) => aiChat.setContextProjectId(id), {immediate: true});
</script>

<template>
    <div class="sticky top-0 py-4 mb-4 -mt-4 bg-gray-50 dark:bg-dark-900 z-20 border-b">
        <div class="flex">
            <form @submit.prevent="submit" class="flex-grow">
                <div class="flex flex-wrap w-full gap-x-8 gap-y-5 items-center justify-start md:justify-end">
                    <div class="flex-grow">
                        <div class="flex flex-col sm:flex-row flex-wrap gap-x-8 gap-y-4 sm:gap-y-5 items-stretch sm:items-center justify-between">
                            <ScopeSelector
                                v-model:project-id="form.project_id"
                                :projects="teamStats.projects"
                                @project-changed="filterChanged"
                            />
                            <div class="flex flex-col sm:flex-row sm:flex-wrap gap-y-3 sm:gap-y-5 gap-x-2 items-stretch sm:items-center w-full sm:w-auto">
                                <div class="flex items-center gap-1" data-tour="presets">
                                    <div class="flex-1 sm:flex-none">
                                        <InputLabel class="hidden" for="rangePreset" value="Preset" />
                                        <SelectInput
                                            id="rangePreset"
                                            v-model="form.preset"
                                            class="mt-1 block w-full sm:w-40 dark:!bg-dark-800"
                                            :options="presets"
                                            @change="presetChanged"
                                        />
                                    </div>
                                    <button type="button" aria-label="Manage Presets" @click="showPresetModal = true" class="hidden sm:block mt-1 p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded hover:bg-gray-100 dark:hover:bg-dark-700">
                                        <GearIcon class="size-5" />
                                    </button>
                                </div>
                                <template v-if="isCustomRange()">
                                    <div class="hidden sm:block">
                                        <DotIcon class="size-4" />
                                    </div>
                                    <CustomRangePicker
                                        ref="customRangePicker"
                                        :range-start="form.range_start"
                                        :range-amount="form.range_amount"
                                        :range-type="form.range_type"
                                        :range-types="rangeTypes"
                                        @apply="applyCustomRange"
                                    />
                                </template>
                                <div class="hidden sm:block">
                                    <DotIcon class="size-4" />
                                </div>
                                <div class="flex flex-row gap-2 sm:contents">
                                    <div class="flex items-center gap-1 flex-1 sm:flex-none">
                                        <div class="flex-1 sm:flex-none">
                                            <InputLabel class="hidden" for="rangeComparison" value="Comparison" />
                                            <SelectInput
                                                id="rangeComparison"
                                                v-model="form.comparison"
                                                :class="comparisonClass"
                                                :options="comparisonTypes"
                                                @change="filterChanged"
                                            />
                                        </div>
                                        <span class="hidden sm:inline-flex">
                                            <InfoTooltip :html="comparisonHelp" placement="bottom" icon-class="size-5 mt-1 ml-1" />
                                        </span>
                                    </div>
                                    <div class="hidden sm:block">
                                        <DotIcon class="size-4" />
                                    </div>
                                    <div v-if="showCohortPicker" class="flex items-center gap-1 flex-1 sm:flex-none">
                                        <CohortPicker :filters="filters" @apply="applyCohort" @clear="clearCohort" />
                                        <span class="hidden sm:inline-flex">
                                            <InfoTooltip
                                                title="Cohort filter"
                                                text="Narrows every metric to users who registered inside the chosen window. The observation period stays whatever you selected separately, so you can follow one signup cohort over time."
                                                placement="bottom"
                                                icon-class="size-5 mt-1 ml-1"
                                            />
                                        </span>
                                    </div>
                                </div>
                                <div v-if="showCohortPicker" class="hidden sm:block">
                                    <DotIcon class="size-4" />
                                </div>
                                <div class="hidden">
                                    <div v-tooltip.left="{ content: `<div class='flex flex-col space-y-4 max-w-64 p-2 text-sm'>${chartDescription}</div>`, html: true }" class="text-gray-400">
                                        <InfoIcon class="size-4" />
                                    </div>
                                </div>
                                <div class="mt-1 sm:mt-0">
                                    <InputLabel class="hidden" for="refresh" value="&nbsp;" />
                                    <SecondaryButton type="submit" aria-label="Reload" class="w-full sm:w-auto justify-center py-3 sm:mt-[3px]" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                        <RefreshIcon class="size-4" />
                                        <span class="ml-2 sm:hidden">Refresh</span>
                                    </SecondaryButton>
                                </div>
                                <button v-if="$page.props.currentPlanOptions?.AiAssistant" type="button" aria-label="Ask AI" v-tooltip="'Ask AI about your stats'" @click="aiChat.toggle()" class="mt-1 sm:mt-[3px] px-3 py-2.5 text-sm rounded-lg border font-medium flex justify-center items-center gap-2 transition-colors" :class="aiChat.isOpen.value ? 'border-indigo-300 bg-indigo-100 text-indigo-700 dark:border-indigo-500/40 dark:bg-indigo-500/20 dark:text-indigo-300' : 'border-indigo-200 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-400 dark:hover:bg-indigo-500/20'">
                                    <StarsIcon class="size-5" />
                                    <span>Ask AI</span>
                                </button>
                                <Link v-else :href="route('spark.portal')" aria-label="Ask AI (upgrade required)" v-tooltip="'The AI assistant is available on paid plans. Upgrade to unlock it.'" class="mt-1 sm:mt-[3px] px-3 py-2.5 text-sm rounded-lg border dark:border-dark-700 flex justify-center items-center gap-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-dark-700">
                                    <StarsIcon class="size-5" />
                                    <span>Ask AI</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div v-if="isStatsGroupSet()" class="w-full bg-indigo-300 dark:bg-indigo-600 dark:text-dark-300 mt-3 p-1.5 rounded-lg">
            <div class="flex flex-wrap gap-2 items-center">
                <SearchIcon class="size-5" />
                <template v-for="statsGroup in statsGroups">
                    <template v-for="group in statsGroup">
                        <div v-if="filters[group.type]" class="bg-indigo-100 dark:bg-indigo-500 border rounded-md px-2 py-0.5">
                            {{ groupLabel(group) }} is <strong>{{ topReferers[statsGroupNames[group.type]] ?? statsGroupNames[group.type] }}</strong>
                            <button class="ml-1 hover:text-red-700" @click="(e) => { form[group.type] = 0; filterChanged(e); }">
                                <CloseIcon class="size-4 inline-block" />
                            </button>
                        </div>
                    </template>
                </template>
                <div v-if="filters.property_value" class="bg-indigo-100 dark:bg-indigo-500 border rounded-md px-2 py-0.5">
                    {{ statsGroupNames.property_value_title ?? 'Property' }} is <strong>{{ statsGroupNames.property_value }}</strong>
                    <button class="ml-1 hover:text-red-700" @click="(e) => { form.property_value = 0; filterChanged(e); }">
                        <CloseIcon class="size-4 inline-block" />
                    </button>
                </div>
                <div v-if="filters.campaign" class="bg-indigo-100 dark:bg-indigo-500 border rounded-md px-2 py-0.5">
                    Campaign is <strong>{{ statsGroupNames.campaign }}</strong>
                    <button class="ml-1 hover:text-red-700" @click="(e) => { form.campaign = 0; filterChanged(e); }">
                        <CloseIcon class="size-4 inline-block" />
                    </button>
                </div>
                <button type="button" aria-label="Reset all filters" v-tooltip="'Reset all filters'" @click="resetFilters" class="ml-auto p-1 hover:text-red-700">
                    <CloseIcon class="size-5 inline-block" />
                </button>
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
