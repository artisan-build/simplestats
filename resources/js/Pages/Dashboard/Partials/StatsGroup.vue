<script setup>
import {onMounted, ref} from 'vue';
import {formatCurrency, formatNumber, formatPercent} from "@/Helpers/NumberHelpers.js";
import {Link, usePage} from "@inertiajs/vue3";
import {XAU} from "@/Helpers/StringHelpers.js";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import axios from 'axios';
import StatsIcon from "@/Components/StatsIcon.vue";

const props = defineProps({
    filters: Object,
    statsGroup: Object,
    statsGroupType: String,
    statsGroupSelected: String,
    topReferers: Object,
    currentProject: Object,
    trackingUsageBits: Object,
});

const page = usePage();

const data = ref([]);
const loading = ref(true);

const filters = Object.assign({}, props.filters);

if (props.statsGroupSelected) {
    filters.statsType = props.statsGroupSelected;
} else {
    filters.statsType = props.statsGroup[0].type;
}

if (! filters.statsSort) {
    switch (true) {
        // case currentProjectHasTrackingUsageBits(['payments']): filters.statsSort = 'net'; break;
        // case currentProjectHasTrackingUsageBits(['users']): filters.statsSort = 'reg'; break;
        default: filters.statsSort = 'visitors';
    }
}

const fetchData = async () => {
    try {
        const response = await axios(route('stats.grouped', filters));
        data.value = await response.data;
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
};

const changeGroup = (statsType, statsGroupType) => {
    filters.statsType = statsType;
    filters.groupType = statsGroupType;
    loading.value = true;
    fetchData();
};

const changeSort = (sort) => {
    filters.statsSort = sort;
    loading.value = true;
    fetchData();
};

const calculatePercentage = (current) => {
    const sort = (filters.statsSort === 'name') ?  'visitors' : filters.statsSort ;

    let highest = data.value.reduce((max, item) => item[sort] > max[sort] ? item : max, data.value[0])[sort] ?? 0;

    // if net is 0 then we try visitors
    // if (highest <= 0) {
    //     highest = data.value.reduce((max, item) => item.visitors > max.visitors ? item : max, data.value[0]).visitors;
    //     return Math.floor((current.visitors / highest) * 100);
    // }

    return Math.floor((current[sort] / highest) * 100);
};

const statsTypeIcon =  (statsGroup, item) => {
    return statsGroup.filter((item) => item.type === filters.statsType)[0].icon;
}

onMounted(fetchData);

function currentProjectHasTrackingUsageBits(bits){
    return bits.every(bit => (props.trackingUsageBits[bit] & props.currentProject.usage));
}

const isDarkMode = ref(document.documentElement.classList.contains('dark'));
const gradientColor = (isDarkMode.value) ? '46, 89, 113' : '220, 236, 254';
</script>

<template>
    <div class="@container flex-grow min-h-96 overflow-x-auto">
        <div v-if="loading" class="flex items-center justify-center h-96">
            <div>
                <svg class="text-gray-300 animate-spin -ml-1 mr-3 h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>
        <div v-else class="p-4 text-sm @md:text-base">
            <div class="flex flex-col @lg:flex-row justify-between mb-4">
                <div class="font-bold text-center mb-3 @lg:mb-0">
                    {{ group2Name[statsGroupType] }}
                </div>
                <div class="flex">
                    <button v-for="stat in statsGroup" class="flex pb-2 px-2 font-medium text-gray-500 dark:text-dark-300 hover:text-indigo-500 border-b" :class="{'text-indigo-600 border-b-indigo-600': filters.statsType === stat.type}" @click="changeGroup(stat.type, statsGroupType)">
                        {{ stat.title }}
                    </button>
                </div>
            </div>
            <div class="overflow-y-auto" style="max-height: 300px;">
                <table class="w-full table-fixed">
                    <tbody>
                        <tr class="sticky bg-white dark:bg-dark-800 top-0 font-medium text-gray-600 dark:text-dark-300">
                            <td style="width: 44%;" class="py-1.5">
                                <button class="whitespace-nowrap" :class="filters.statsSort === 'name' ? 'text-indigo-600' : ''" @click="changeSort('name')">
                                    <span>Name</span>
                                    <svg v-if="filters.statsSort === 'name'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 hidden @md:inline-block">
                                        <path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </td>
                            <td v-if="currentProjectHasTrackingUsageBits(['visitors'])" style="width: 12%;" class="p-1.5 text-right">
                                <button class="whitespace-nowrap" :class="filters.statsSort === 'visitors' ? 'text-indigo-600' : ''" @click="changeSort('visitors')">
                                    <span class="inline-block @2xl:hidden">Visits</span>
                                    <span class="hidden @2xl:inline-block">Visitors</span>
                                    <svg v-if="filters.statsSort === 'visitors'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 hidden @md:inline-block">
                                        <path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </td>
                            <td v-if="currentProjectHasTrackingUsageBits(['users'])" style="width: 10%;" class="p-1.5 text-right">
                                <button class="whitespace-nowrap" :class="filters.statsSort === 'reg' ? 'text-indigo-600' : ''" @click="changeSort('reg')">
                                    <span>Regs</span>
                                    <svg v-if="filters.statsSort === 'reg'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 hidden @md:inline-block">
                                        <path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </td>
                            <td v-if="currentProjectHasTrackingUsageBits(['users', 'visitors'])" style="width: 12%;" class="p-1.5 text-right">
                                <button class="whitespace-nowrap" :class="filters.statsSort === 'cr' ? 'text-indigo-600' : ''" @click="changeSort('cr')">
                                    <span class="inline-block @2xl:hidden">CR</span>
                                    <span class="hidden @2xl:inline-block">Conv.</span>
                                    <svg v-if="filters.statsSort === 'cr'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 hidden @md:inline-block">
                                        <path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </td>
                            <td v-if="currentProjectHasTrackingUsageBits(['users'])" style="width: 10%;" class="p-1.5 text-right">
                                <button class="whitespace-nowrap" :class="filters.statsSort === 'dau' ? 'text-indigo-600' : ''" @click="changeSort('dau')">
                                    <span>{{ XAU(filters.rangeType) }}</span><!-- the span removes whitespace -->
                                    <svg v-if="filters.statsSort === 'dau'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 hidden @md:inline-block">
                                        <path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </td>
                            <td v-if="currentProjectHasTrackingUsageBits(['payments'])" style="width: 12%;" class="p-1.5 text-right">
                                <button class="whitespace-nowrap" :class="filters.statsSort === 'net' ? 'text-indigo-600' : ''" @click="changeSort('net')">
                                    <span class="inline-block @2xl:hidden">Rev.</span>
                                    <span class="hidden @2xl:inline-block">Revenue</span>
                                    <svg v-if="filters.statsSort === 'net'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 hidden @md:inline-block">
                                        <path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        <tr v-for="item in data">
                            <td class="py-1.5">
                                <!-- direction: rtl; is that on small screens the page uris got truncated from left so that you can see the end of the uri with prio -->
                                <div class="flex items-center h-9 w-full px-2 py-1.5 rounded-l"
                                     :style="'background: linear-gradient(to right, rgba('+gradientColor+', 1) '+calculatePercentage(item)+'%, rgba('+gradientColor+', 0) '+calculatePercentage(item)+'%);'"
                                >
                                    <Link class="group truncate" :style="(statsGroupType === 'page') ? 'direction: rtl;' : '' " :class="{'text-indigo-600 underline': filters[filters.statsType] === item.type_id}" preserve-scroll :href="route('dashboard', {...filters, [filters.statsType]: item.type_id ?? -1})">
                                        <StatsIcon v-if="statsGroupType !== 'page'" :statsName="item.name" :statsType="filters.statsType" :statsIconType="statsTypeIcon(statsGroup, item)" />
                                        <span class="group-hover:underline ml-2" v-tooltip="{ content: topReferers[item.name] ?? item.name, delay: 2000 }">{{ topReferers[item.name] ?? item.name }}</span>
                                    </Link>
                                </div>
                            </td>
                            <td v-if="currentProjectHasTrackingUsageBits(['visitors'])" class="p-1.5 text-right">{{ formatNumber(Number(item.visitors)) }}</td>
                            <td v-if="currentProjectHasTrackingUsageBits(['users'])" class="p-1.5 text-right">{{ formatNumber(Number(item.reg)) }}</td>
                            <td v-if="currentProjectHasTrackingUsageBits(['users', 'visitors'])" class="p-1.5 text-right">{{ formatPercent(Number(item.cr)) }}</td>
                            <td v-if="currentProjectHasTrackingUsageBits(['users'])" class="p-1.5 text-right">{{ formatNumber(Number(item.dau)) }}</td>
                            <td v-if="currentProjectHasTrackingUsageBits(['payments'])" class="p-1.5 text-right">{{ formatCurrency(item.net, {}, $page.props.teamCurrency) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="data.length >= 5" class="flex justify-center mt-2">
                <SecondaryButton v-if="! filters.limit" @click="() => {filters.limit = (filters.limit ?? 0) + 100; fetchData()}">
                    Load more
                </SecondaryButton>
            </div>
        </div>
    </div>
</template>
