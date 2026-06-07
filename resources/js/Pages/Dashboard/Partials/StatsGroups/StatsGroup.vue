<script setup>
import {onMounted, ref} from 'vue';
import {Link} from "@inertiajs/vue3";
import {XAU} from "@/Helpers/StringHelpers.js";
import axios from 'axios';
import StatsIcon from "@/Components/StatsIcon.vue";
import StatsGroupTable from "./StatsGroupTable.vue";
import {useTrackingUsage} from "@/Composables/useTrackingUsage.js";

const props = defineProps({
    filters: Object,
    statsGroup: Object,
    statsGroupType: String,
    statsGroupSelected: String,
    topReferers: Object,
    currentProject: Object,
    trackingUsageBits: Object,
});

const data = ref([]);
const dataPrevious = ref([]);
const loading = ref(true);

const {currentProjectHasTrackingUsageBits} = useTrackingUsage(props.currentProject, props.trackingUsageBits);

const filters = Object.assign({}, props.filters);

if (props.statsGroupSelected) {
    filters.stats_type = props.statsGroupSelected;
} else {
    filters.stats_type = props.statsGroup[0].type;
}

const isEventGroup = props.statsGroupType === 'event';

if (! filters.stats_sort) {
    filters.stats_sort = isEventGroup ? 'triggered' : 'visitors';
}

const columns = isEventGroup
    ? [
        {key: 'visitors', label: 'Visitors', format: 'number', width: '15%'},
        {key: 'triggered', label: 'Triggered', format: 'number', width: '15%'},
        {key: 'cr', label: 'Conv. Rate', labelSmall: 'CR', format: 'percent', width: '18%'},
        {key: 'net', label: 'Revenue', labelSmall: 'Rev.', format: 'currency', width: '18%', visible: currentProjectHasTrackingUsageBits(['payments'])},
    ]
    : [
        {key: 'visitors', label: 'Visitors', labelSmall: 'Visits', format: 'number', width: '12%', visible: currentProjectHasTrackingUsageBits(['visitors'])},
        {key: 'reg', label: 'Regs', format: 'number', width: '10%', visible: currentProjectHasTrackingUsageBits(['users'])},
        {key: 'cr', label: 'Conv.', labelSmall: 'CR', format: 'percent', width: '12%', visible: currentProjectHasTrackingUsageBits(['users', 'visitors'])},
        {key: 'dau', label: XAU(filters.range_type), format: 'number', width: '10%', visible: currentProjectHasTrackingUsageBits(['users'])},
        {key: 'net', label: 'Revenue', labelSmall: 'Rev.', format: 'currency', width: '12%', visible: currentProjectHasTrackingUsageBits(['payments'])},
    ];

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
        name-label="Name"
        :name-width="isEventGroup ? '34%' : '44%'"
        :percentage="calculatePercentage"
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
            <Link class="group truncate" :style="(statsGroupType === 'page') ? 'direction: rtl;' : '' " :class="{'text-indigo-600 dark:text-indigo-400 underline': filters[filters.stats_type] === item.type_id}" preserve-scroll :href="route('dashboard', {...filters, [filters.stats_type]: item.type_id ?? -1})">
                <StatsIcon v-if="statsGroupType !== 'page' && statsGroupType !== 'event'" :statsName="item.name" :statsType="filters.stats_type" :statsIconType="statsTypeIcon(statsGroup, item)" />
                <span class="group-hover:underline ml-2" v-tooltip="{ content: topReferers[item.name] ?? item.name, delay: 2000 }">{{ topReferers[item.name] ?? item.name }}</span>
            </Link>
        </template>
    </StatsGroupTable>
</template>
