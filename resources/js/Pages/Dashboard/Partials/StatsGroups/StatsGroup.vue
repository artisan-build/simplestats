<script setup>
import {onMounted, ref} from 'vue';
import {Link} from "@inertiajs/vue3";
import {XAU} from "@/Helpers/StringHelpers.js";
import axios from 'axios';
import StatsIcon from "@/Components/StatsIcon.vue";
import StatsGroupTable from "./StatsGroupTable.vue";
import {useUsage} from "@/Composables/useUsage.js";

const props = defineProps({
    filters: Object,
    statsGroup: Object,
    statsGroupType: String,
    statsGroupSelected: String,
    topReferers: Object,
    currentProject: Object,
    usageBits: Object,
    baseRoute: {type: String, default: 'dashboard'},
    routeParams: {type: Object, default: () => ({})},
    // Metric detail view: curated breakdown columns + the primary column to highlight/sort by.
    breakdownColumns: {type: Array, default: null},
    breakdownPrimary: {type: String, default: null},
});

const data = ref([]);
const dataPrevious = ref([]);
const loading = ref(true);

const {currentProjectHasUsages} = useUsage(props.currentProject, props.usageBits);

const filters = Object.assign({}, props.filters);

if (props.statsGroupSelected) {
    filters.stats_type = props.statsGroupSelected;
} else {
    filters.stats_type = props.statsGroup[0].type;
}

const isEventGroup = props.statsGroupType === 'event';

const xauLabel = XAU(filters.range_type);

// Every breakdown column a curated set can request, with its label/format and the
// tracking bits that gate it.
const columnRegistry = {
    visitors: {key: 'visitors', label: 'Visitors', labelSmall: 'Visits', format: 'number', bits: ['visitors']},
    reg:      {key: 'reg', label: 'Regs', format: 'number', bits: ['users']},
    cr:       {key: 'cr', label: 'Conv.', labelSmall: 'CR', format: 'percent', bits: ['users', 'visitors']},
    dau:      {key: 'dau', label: xauLabel, format: 'number', bits: ['users']},
    net:      {key: 'net', label: 'Revenue', labelSmall: 'Rev.', format: 'currency', bits: ['payments']},
    arpv:     {key: 'arpv', label: 'ARPV', format: 'currency', bits: ['payments', 'visitors']},
    arpu:     {key: 'arpu', label: 'ARPU', format: 'currency', bits: ['payments', 'users']},
    arpdau:   {key: 'arpdau', label: 'ARPDAU', format: 'currency', bits: ['payments', 'users']},
    reg2dau:  {key: 'reg2dau', label: 'Reg→Active', labelSmall: 'R→A', format: 'percent', bits: ['users']},
    time_to_convert: {key: 'time_to_convert', label: 'Time to Conv.', labelSmall: 'TTC', format: 'days', bits: ['visitors', 'users']},
    mrr:      {key: 'mrr', label: 'MRR', format: 'currency', bits: ['subscription']},
    subscribers: {key: 'subscribers', label: 'Subs', format: 'number', bits: ['subscription']},
    arps:     {key: 'arps', label: 'ARPS', format: 'currency', bits: ['subscription']},
};

const defaultColumns = isEventGroup
    ? [
        {key: 'visitors', label: 'Visitors', format: 'number', width: '15%'},
        {key: 'triggered', label: 'Triggered', format: 'number', width: '15%'},
        {key: 'cr', label: 'Conv. Rate', labelSmall: 'CR', format: 'percent', width: '18%'},
        {key: 'net', label: 'Revenue', labelSmall: 'Rev.', format: 'currency', width: '18%', visible: currentProjectHasUsages(['payments'])},
    ]
    : [
        {key: 'visitors', label: 'Visitors', labelSmall: 'Visits', format: 'number', width: '12%', visible: currentProjectHasUsages(['visitors'])},
        {key: 'reg', label: 'Regs', format: 'number', width: '10%', visible: currentProjectHasUsages(['users'])},
        {key: 'cr', label: 'Conv.', labelSmall: 'CR', format: 'percent', width: '12%', visible: currentProjectHasUsages(['users', 'visitors'])},
        {key: 'dau', label: xauLabel, format: 'number', width: '10%', visible: currentProjectHasUsages(['users'])},
        {key: 'net', label: 'Revenue', labelSmall: 'Rev.', format: 'currency', width: '12%', visible: currentProjectHasUsages(['payments'])},
    ];

// Build the active KPI's curated column set, intersected with the project's tracking
// bits. If the highlighted primary doesn't survive that filter, fall back to defaults.
const buildCuratedColumns = () => {
    if (isEventGroup || ! props.breakdownColumns?.length) {
        return null;
    }

    const cols = props.breakdownColumns
        .map((key) => columnRegistry[key])
        .filter(Boolean)
        .filter((col) => ! col.bits || currentProjectHasUsages(col.bits));

    if (! cols.some((col) => col.key === props.breakdownPrimary)) {
        return null;
    }

    const width = `${Math.floor(56 / cols.length)}%`;

    return cols.map((col) => ({...col, width}));
};

const curatedColumns = buildCuratedColumns();
const columns = curatedColumns ?? defaultColumns;
const highlightColumn = curatedColumns ? props.breakdownPrimary : undefined;

if (! filters.stats_sort) {
    filters.stats_sort = isEventGroup
        ? 'triggered'
        : (curatedColumns ? props.breakdownPrimary : 'visitors');
}

// The MRR columns are a point-in-time snapshot the backend only computes on request,
// so opt in whenever the curated set surfaces them for a recurring-revenue project.
if (curatedColumns
    && ['mrr', 'subscribers', 'arps'].includes(props.breakdownPrimary)
    && currentProjectHasUsages(['subscription'])
) {
    filters.metric = 'mrr';
}

// The per-dimension time-to-convert median is likewise opt-in (an extra grouped query).
if (curatedColumns
    && props.breakdownPrimary === 'time_to_convert'
    && currentProjectHasUsages(['visitors', 'users'])
) {
    filters.metric = 'time_to_convert';
}

const fetchData = async () => {
    try {
        const response = await axios(route('stats.grouped', filters));
        const payload = response.data;

        // backwards-compatible: API may return either {data, data_previous} or a plain array
        if (Array.isArray(payload)) {
            data.value = payload;
            dataPrevious.value = [];
        } else {
            data.value = payload.data ?? [];
            dataPrevious.value = payload.data_previous ?? [];
        }
    } catch (error) {
        console.error('Error fetching data:', error);
    } finally {
        loading.value = false;
    }
};

const group2Name = {
    track: 'Tracking',
    page: 'Pages',
    location: 'Locations',
    device: 'Devices',
    event: 'Custom Events',
};

const changeGroup = (statsType, statsGroupType) => {
    filters.stats_type = statsType;
    filters.group_type = statsGroupType;
    loading.value = true;
    fetchData();
};

const changeSort = (sort) => {
    filters.stats_sort = sort;
    loading.value = true;
    fetchData();
};

const loadMore = () => {
    filters.limit = (filters.limit ?? 0) + 100;
    fetchData();
};

const calculatePercentage = (current) => {
    const defaultSort = isEventGroup ? 'triggered' : 'visitors';
    const sort = (filters.stats_sort === 'name' || (isEventGroup && filters.stats_sort === 'visitors')) ? defaultSort : filters.stats_sort ;

    let highest = data.value.reduce((max, item) => item[sort] > max[sort] ? item : max, data.value[0])[sort] ?? 0;

    // if net is 0 then we try visitors
    // if (highest <= 0) {
    //     highest = data.value.reduce((max, item) => item.visitors > max.visitors ? item : max, data.value[0]).visitors;
    //     return Math.floor((current.visitors / highest) * 100);
    // }

    return Math.floor((current[sort] / highest) * 100);
};

const statsTypeIcon =  (statsGroup, item) => {
    return statsGroup.filter((item) => item.type === filters.stats_type)[0].icon;
}

onMounted(fetchData);
</script>

<template>
    <StatsGroupTable
        :title="group2Name[statsGroupType]"
        :loading="loading"
        :data="data"
        :data-previous="dataPrevious"
        :columns="columns"
        :sort="filters.stats_sort"
        :highlight-column="highlightColumn"
        name-label="Name"
        :name-width="isEventGroup ? '34%' : '44%'"
        :percentage="calculatePercentage"
        :empty-text="isEventGroup ? 'No custom events in the selected period.' : undefined"
        :can-load-more="data.length >= 5 && ! filters.limit"
        @sort="changeSort"
        @load-more="loadMore"
    >
        <template #tabs>
            <div class="flex">
                <button v-for="stat in statsGroup" class="flex pb-2 px-2 font-medium text-gray-500 dark:text-dark-300 hover:text-indigo-500 border-b" :class="{'text-indigo-600 border-b-indigo-600': filters.stats_type === stat.type}" @click="changeGroup(stat.type, statsGroupType)">
                    {{ stat.title }}
                </button>
            </div>
        </template>
        <template #name-cell="{item}">
            <!-- direction: rtl; is that on small screens the page uris got truncated from left so that you can see the end of the uri with prio -->
            <Link class="group truncate" :style="(statsGroupType === 'page') ? 'direction: rtl;' : '' " :class="{'text-indigo-600 dark:text-indigo-400 underline': filters[filters.stats_type] === item.type_id}" preserve-scroll :href="route(baseRoute, {...routeParams, ...filters, [filters.stats_type]: item.type_id ?? -1})">
                <StatsIcon v-if="statsGroupType !== 'page' && statsGroupType !== 'event'" :statsName="item.name" :statsType="filters.stats_type" :statsIconType="statsTypeIcon(statsGroup, item)" />
                <span class="group-hover:underline ml-2" v-tooltip="{ content: topReferers[item.name] ?? item.name, delay: 2000 }">{{ topReferers[item.name] ?? item.name }}</span>
            </Link>
        </template>
    </StatsGroupTable>
</template>
