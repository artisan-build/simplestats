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

const fetchData = async () => {
    try {
        // console.log(filters)
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
    <div class="flex-grow min-h-96 overflow-x-auto ">
        <div v-if="loading" class="flex items-center justify-center h-96">
            <div>
                <svg class="text-gray-300 animate-spin -ml-1 mr-3 h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>
        <div v-else class="p-4">
            <div class="flex justify-between mb-4">
                <div class="font-bold text-center">{{ group2Name[statsGroupType] }}</div>
                <div class="flex">
                    <button v-for="stat in statsGroup" class="flex pb-2 px-2 font-medium text-sm text-gray-500 hover:text-indigo-500 border-b" :class="{'text-indigo-600  border-b-indigo-600': filters.statsType === stat.type}" @click="changeGroup(stat.type, statsGroupType)">
                        {{ stat.title }}
                    </button>
                </div>
            </div>
            <div class="overflow-y-auto" style="max-height: 300px;">
                <table class="w-full text-sm">
                    <tr class="sticky top-0 bg-white font-medium text-gray-600">
                        <td class="p-1 py-1.5">Name</td>
                        <td class="w-1 p-1.5 py-1.5 text-left">Visitors</td>
                        <td class="w-1 p-1.5 py-1.5 text-left">Regs</td>
                        <td class="w-1 p-1.5 py-1.5 text-left">CR</td>
                        <td class="w-1 p-1.5 py-1.5 text-right">{{ XAU(filters.rangeType) }}</td>
                        <td class="w-1 p-1.5 py-1.5 text-left">Revenue</td>
                    </tr>
                    <tr v-for="item in data">
                        <td class="p-1.5">
                            <div class="flex items-center h-9 w-full px-2 py-1.5 rounded-l" :style="'background: linear-gradient(to right, rgba(238, 242, 255, 1) '+calculatePercentage(item)+'%, rgba(238, 242, 255, 0) '+calculatePercentage(item)+'%);'">
                                <Link preserve-scroll :href="route('dashboard', {...filters, [filters.statsType]: item.type_id ?? -1})" class="group" :class="{'text-indigo-600 underline': filters[filters.statsType] === item.type_id}">
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
                        <td class="p-1.5 text-right">{{ formatPercent(Number(item.visitors) / Number(item.reg)) }}</td>
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
