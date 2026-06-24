<script setup>
import {formatMap} from "@/Helpers/NumberHelpers.js";
import {formatRoas, profitClass as profitClassBase, roasClass as roasClassBase} from "@/Helpers/CampaignKpiHelpers.js";
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    summary: Object,
    showsRegistrations: {type: Boolean, default: false},
});

const page = usePage();

const neutral = 'text-gray-950 dark:text-white';
const currency = (cents) => formatMap.currency(cents, {notation: 'standard'}, page.props.teamCurrency);
const roas = formatRoas;
const roasClass = (value) => roasClassBase(value, neutral);
const profitClass = (value) => profitClassBase(value, neutral);
</script>

<template>
    <div class="space-y-2">
        <div class="text-sm font-medium text-gray-500 dark:text-dark-400">
            Campaign performance: <span class="font-semibold text-gray-700 dark:text-dark-200">{{ summary.name }}</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4" :class="showsRegistrations ? 'lg:grid-cols-5' : 'lg:grid-cols-4'">
            <div class="bg-white dark:bg-dark-800 border rounded-md py-2 px-3">
                <div class="text-sm font-medium tracking-wider text-gray-700 dark:text-dark-400">Spend</div>
                <div class="mt-1 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ currency(summary.spend) }}</div>
            </div>
            <div class="bg-white dark:bg-dark-800 border rounded-md py-2 px-3">
                <div class="text-sm font-medium tracking-wider text-gray-700 dark:text-dark-400">Revenue</div>
                <div class="mt-1 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ currency(summary.net) }}</div>
            </div>
            <div class="bg-white dark:bg-dark-800 border rounded-md py-2 px-3">
                <div class="text-sm font-medium tracking-wider text-gray-700 dark:text-dark-400">Net profit</div>
                <div class="mt-1 text-2xl font-semibold tracking-tight" :class="profitClass(summary.net_profit)">{{ currency(summary.net_profit) }}</div>
            </div>
            <div class="bg-white dark:bg-dark-800 border rounded-md py-2 px-3">
                <div class="text-sm font-medium tracking-wider text-gray-700 dark:text-dark-400">ROAS</div>
                <div class="mt-1 text-2xl font-semibold tracking-tight" :class="roasClass(summary.roas)">{{ roas(summary.roas) }}</div>
            </div>
            <div v-if="showsRegistrations" class="bg-white dark:bg-dark-800 border rounded-md py-2 px-3">
                <div class="text-sm font-medium tracking-wider text-gray-700 dark:text-dark-400">CAC</div>
                <div class="mt-1 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ summary.cac === null ? '–' : currency(summary.cac) }}</div>
            </div>
        </div>
    </div>
</template>
