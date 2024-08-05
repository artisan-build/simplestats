<script setup>
import {onMounted, ref} from 'vue';
import {formatCurrency, formatNumber, formatPercent} from "@/Helpers/NumberHelpers.js";
import {Link} from "@inertiajs/vue3";
import _ from 'lodash';
import {get} from 'country-flag-emoji';
import {XAU} from "@/Helpers/StringHelpers.js";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const props = defineProps({
    filters: Object,
    statsGroup: Object,
    statsGroupType: String,
    statsGroupSelected: String,
    topReferers: Object,
});

const data = ref([]);
const loading = ref(true);

const filters = Object.assign({}, props.filters);

if (props.statsGroupSelected) {
    filters.statsType = props.statsGroupSelected;
} else {
    filters.statsType = props.statsGroup[0].type;
}

if (! filters.statsSort) {
    filters.statsSort = 'net';
}

const fetchData = async () => {
    try {
        const response = await fetch(route('stats.grouped', filters));
        data.value = await response.json();
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
    let highest = data.value.reduce((max, item) => item.net > max.net ? item : max, data.value[0]).net;

    // if net is 0 then we try reg
    if (highest <= 0) {
        highest = data.value.reduce((max, item) => item.reg > max.reg ? item : max, data.value[0]).reg;
        return Math.floor((current.reg / highest) * 100);
    }

    return Math.floor((current.net / highest) * 100);
};

const statsTypeIcon =  (statsGroup, item) => {
    return statsGroup.filter((item) => item.type === filters.statsType)[0].icon;
}

onMounted(fetchData);
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
            <div class="flex justify-between mb-4">
                <div class="font-bold text-center hidden @md:block">{{ group2Name[statsGroupType] }}</div>
                <div class="flex">
                    <button v-for="stat in statsGroup" class="flex pb-2 px-2 font-medium text-gray-500 hover:text-indigo-500 border-b" :class="{'text-indigo-600  border-b-indigo-600': filters.statsType === stat.type}" @click="changeGroup(stat.type, statsGroupType)">
                        {{ stat.title }}
                    </button>
                </div>
            </div>
            <div class="overflow-y-auto" style="max-height: 300px;">
                <table class="w-full table-fixed">
                    <tr class="sticky top-0 bg-white font-medium text-gray-600">
                        <td style="width: 44%;" class="py-1.5">Name</td>
                        <td style="width: 12%;" class="p-1.5 text-right">
                            <button class="whitespace-nowrap" :class="filters.statsSort === 'visitors' ? 'text-indigo-600' : ''" @click="changeSort('visitors')">
                                <span class="inline-block @xl:hidden">Visits</span><!-- the span removes whitespace -->
                                <span class="hidden @xl:inline-block">Visitors</span>
                                <svg v-if="filters.statsSort === 'visitors'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 hidden @md:inline-block">
                                    <path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </td>
                        <td style="width: 10%;" class="p-1.5 text-right">
                            <button class="whitespace-nowrap" :class="filters.statsSort === 'reg' ? 'text-indigo-600' : ''" @click="changeSort('reg')">
                                <span>Regs</span><!-- the span removes whitespace -->
                                <svg v-if="filters.statsSort === 'reg'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 hidden @md:inline-block">
                                    <path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </td>
                        <td style="width: 12%;" class="p-1.5 text-right">
                            <button class="whitespace-nowrap" :class="filters.statsSort === 'cr' ? 'text-indigo-600' : ''" @click="changeSort('cr')">
                                <span>CR</span><!-- the span removes whitespace -->
                                <svg v-if="filters.statsSort === 'cr'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 hidden @md:inline-block">
                                    <path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </td>
                        <td style="width: 10%;" class="p-1.5 text-right">
                            <button class="whitespace-nowrap" :class="filters.statsSort === 'dau' ? 'text-indigo-600' : ''" @click="changeSort('dau')">
                                <span>{{ XAU(filters.rangeType) }}</span><!-- the span removes whitespace -->
                                <svg v-if="filters.statsSort === 'dau'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 hidden @md:inline-block">
                                    <path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </td>
                        <td style="width: 12%;" class="p-1.5 text-right">
                            <button class="whitespace-nowrap" :class="filters.statsSort === 'net' ? 'text-indigo-600' : ''" @click="changeSort('net')">
                                <span>Rev.</span><!-- the span removes whitespace -->
                                <svg v-if="filters.statsSort === 'net'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 hidden @md:inline-block">
                                    <path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                    <tr v-for="item in data">
                        <td class="py-1.5">
                            <div class="flex items-center h-9 w-full px-2 py-1.5 rounded-l" :style="'background: linear-gradient(to right, rgba(238, 242, 255, 1) '+calculatePercentage(item)+'%, rgba(238, 242, 255, 0) '+calculatePercentage(item)+'%);'">
                                <Link class="group truncate" :class="{'text-indigo-600 underline': filters[filters.statsType] === item.type_id}" preserve-scroll :href="route('dashboard', {...filters, [filters.statsType]: item.type_id ?? -1})">
                                    <span v-if="statsTypeIcon(statsGroup, item) === 'favicon'">
                                        <img v-if="item.type_id == -1" class="w-4 h-4 align-sub inline-block" :src="'/icons/track_referer_default.png'" :alt="item.name">
                                        <img v-else class="w-4 h-4 align-sub inline-block" :src="'/icons/'+ _.snakeCase(filters.statsType) +'/'+item.name+'.ico'" :alt="item.name">
                                    </span>
                                    <img v-else-if="statsTypeIcon(statsGroup, item) === 'path'" class="w-4 h-4 align-sub inline-block" :src="'/icons/'+ _.snakeCase(filters.statsType) +'/'+ _.snakeCase(item.name) +'.svg'" :alt="item.name">
                                    <span v-else-if="statsTypeIcon(statsGroup, item) === 'country'" class="text-lg align-sub mt-1 inline-block">{{ get((item.name.match(/\((.*?)\)/) || [])[1])?.emoji ?? '🌎' }}</span>
                                    <span class="group-hover:underline ml-2">{{ topReferers[item.name] ?? item.name }}</span>
                                </Link>
                            </div>
                        </td>
                        <td class="p-1.5 text-right">{{ formatNumber(Number(item.visitors)) }}</td>
                        <td class="p-1.5 text-right">{{ formatNumber(Number(item.reg)) }}</td>
                        <td class="p-1.5 text-right">{{ formatPercent(Number(item.cr)) }}</td>
                        <td class="p-1.5 text-right">{{ formatNumber(Number(item.dau)) }}</td>
                        <td class="p-1.5 text-right">{{ formatCurrency(item.net, {}, $page.props.teamCurrency) }}</td>
                    </tr>
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
