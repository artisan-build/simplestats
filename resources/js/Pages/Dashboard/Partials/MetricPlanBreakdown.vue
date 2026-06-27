<script setup>
import {usePage} from "@inertiajs/vue3";
import {formatMap} from "@/Helpers/NumberHelpers.js";

const props = defineProps({
    // [{plan, subscribers, mrr}], null when the project does not track subscriptions
    planBreakdown: {type: Array, default: null},
});

const page = usePage();

const money = (value) => formatMap.currency(value, {minimumFractionDigits: 0, maximumFractionDigits: 0}, page.props.teamCurrency);
const count = (value) => formatMap.number(value, {minimumFractionDigits: 0, maximumFractionDigits: 0});
</script>

<template>
    <div class="bg-white dark:bg-dark-800 border rounded-md">
        <div class="px-4 py-3 border-b dark:border-dark-700 text-sm font-medium tracking-wider text-gray-700 dark:text-dark-400">
            By plan
        </div>
        <table v-if="planBreakdown && planBreakdown.length" class="w-full text-sm">
            <thead>
                <tr class="text-gray-500 dark:text-dark-400 text-left">
                    <th class="px-4 py-2 font-medium">Plan</th>
                    <th class="px-4 py-2 font-medium text-right">Subscribers</th>
                    <th class="px-4 py-2 font-medium text-right">MRR</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in planBreakdown" :key="row.plan ?? 'unknown'" class="border-t dark:border-dark-700">
                    <td class="px-4 py-2 text-gray-950 dark:text-white">{{ row.plan ?? 'Unknown' }}</td>
                    <td class="px-4 py-2 text-right text-gray-700 dark:text-dark-300">{{ count(row.subscribers) }}</td>
                    <td class="px-4 py-2 text-right text-gray-700 dark:text-dark-300">{{ money(row.mrr) }}</td>
                </tr>
            </tbody>
        </table>
        <p v-else class="px-4 py-6 text-sm text-gray-500 dark:text-dark-400">
            No active subscriptions in the selected scope.
        </p>
    </div>
</template>
