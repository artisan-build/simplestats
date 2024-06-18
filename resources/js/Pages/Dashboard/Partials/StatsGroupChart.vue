<script setup>
import {ref, onMounted} from 'vue';
import {formatCurrency, formatNumber} from "@/Helpers/NumberHelpers.js";
import {Link} from "@inertiajs/vue3";

const props = defineProps({
    statsGroup: Object,
    statsType: String,
    filters: Object,
});

const data = ref([]);
const loading = ref(true);

const filters = Object.assign({}, props.filters);
if (! filters.groupType) {
    filters.groupType = props.statsGroup[0].type;
}

const fetchData = async () => {
    try {
        // console.log(filters)
        const response = await fetch(route('stats.grouped', filters));
        const result = await response.json();
        data.value = result;
    } catch (error) {
        console.error('Error fetching data:', error);
    } finally {
        loading.value = false;
    }
};

const group2Name = {
    track: 'UTMs',
    location: 'Countries',
    device: 'Devices',
};

const changeGroup = (stat) => {
    filters.groupType = stat.type;
    loading.value = true;
    fetchData();
};

onMounted(fetchData);
</script>

<template>
    <div class="min-h-96">
        <div v-if="loading" class="flex items-center justify-center h-96">
            <div>
                <svg class="text-gray-300 animate-spin -ml-1 mr-3 h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>
        <div v-else class="px-4 py-5">
            <div class="flex justify-between mb-2">
                <div class="font-bold">{{ group2Name[statsType] }}</div>
                <div class="flex gap-2">
                    <button v-for="stat in statsGroup" class="font-medium text-gray-500 hover:text-indigo-500" :class="{'text-indigo-600 underline': filters.groupType === stat.type}" @click="changeGroup(stat)">
                        {{ stat.title }}
                    </button>
                </div>
            </div>
            <table class="w-full text-sm">
                <tr class="font-medium text-gray-600">
                    <td class="p-1 py-1.5">Title</td>
                    <td class="w-1 p-1 py-1.5 text-right">Revenue</td>
                    <td class="w-1 p-1 py-1.5 text-right">Regs</td>
                    <td class="w-1 p-1 py-1.5 text-right">MAU</td>
                </tr>
                <tr v-for="item in data">
                    <td class="p-1">
                        <Link preserve-scroll :href="route('dashboard', {...filters, [filters.groupType]: item.type_id})" class="hover:underline" :class="{'text-indigo-600 underline': filters[filters.groupType] == item.type_id}">
                            {{ item.name ?? 'Direct / Unknown' }}
                        </Link>
                    </td>
                    <td class="p-1 text-right">{{ formatCurrency(item.net, {}, $page.props.teamCurrency) }}</td>
                    <td class="p-1 text-right">{{ formatNumber(item.reg) }}</td>
                    <td class="p-1 text-right">{{ formatNumber(item.mau) }}</td>
                </tr>
            </table>
        </div>
    </div>
</template>
