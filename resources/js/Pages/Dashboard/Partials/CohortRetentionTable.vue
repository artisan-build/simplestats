<script setup>
import {computed} from "vue";
import {Link} from "@inertiajs/vue3";
import {formatDate} from "@/Helpers/NumberHelpers.js";

const props = defineProps({
    cohorts: Object,
    filters: Object,
});

// Same readable date rendering the Apex charts use for their axis (StatsCharts.vue),
// so cohort labels match the rest of the dashboard.
const formatLabel = (label) => formatDate(label, props.filters.range_type);

// Drill from a cohort into the dashboard, scoped to exactly this registration
// window. The observation period stays the one selected here: the retention
// range already spans the cohorts, so it covers their lifetime without overriding it.
const dashboardLink = (row) => route('dashboard', {
    ...props.filters,
    cohort_start: row.cohort_start,
    cohort_end: row.cohort_end,
    cohort_label: formatLabel(row.cohort),
});

const periodNoun = computed(() => ({
    hours: 'Hour',
    days: 'Day',
    weeks: 'Week',
    months: 'Month',
    years: 'Year',
}[props.filters.range_type] ?? 'Period'));

const periodHeaders = computed(() =>
    Array.from({length: props.cohorts.period_count}, (_, i) => `${periodNoun.value} ${i}`)
);

// Opaque emerald swatch whose lightness scales with the retention share, so it
// reads as a heatmap on any background (light or dark) without per-mode classes.
const cellStyle = (value) => {
    if (value === null || value === undefined) {
        return {};
    }

    const lightness = 96 - (value / 100) * 60;

    return {
        backgroundColor: `hsl(160, 84%, ${lightness}%)`,
        color: lightness < 60 ? '#ffffff' : '#064e3b',
    };
};

const isEmpty = (value) => value === null || value === undefined;

const formatValue = (value) => isEmpty(value) ? '–' : `${value}%`;

// The column header is the relative age period; the real calendar bucket of a cell
// differs per row, so it lives in a hover tooltip instead (cohort idx + period maps
// into the absolute bucket_labels).
const cellTooltip = (row, period, value) => {
    const label = props.cohorts.bucket_labels?.[row.idx + period];

    if (label === undefined || label === null) {
        return 'Not measurable yet';
    }

    const readable = formatLabel(label);

    if (!isEmpty(value)) {
        return `${readable}: ${value}% returned`;
    }

    return row.size === 0 ? `${readable}: no registrations` : `${readable}: not yet`;
};
</script>

<template>
    <div class="bg-white dark:bg-dark-800 border dark:border-dark-700 rounded-md overflow-hidden">
        <div class="px-4 py-3 border-b dark:border-dark-700">
            <h3 class="font-medium text-gray-900 dark:text-dark-100">Retention by registration cohort</h3>
            <p class="text-sm text-gray-500 dark:text-dark-400">
                Share of each cohort that returned (logged in again) in the following {{ periodNoun.toLowerCase() }}s.
                Read across a row to see how a cohort's retention decays over time.
            </p>
        </div>

        <div v-if="cohorts.cohorts.length" class="overflow-x-auto">
            <table class="min-w-full text-sm border-separate border-spacing-0.5 p-2">
                <thead>
                    <tr class="text-gray-500 dark:text-dark-400">
                        <th class="sticky left-0 z-10 bg-white dark:bg-dark-800 px-3 py-2 text-left font-medium">Cohort</th>
                        <th class="px-3 py-2 text-right font-medium">Regs</th>
                        <th v-for="header in periodHeaders" :key="header" class="px-3 py-2 text-center font-medium whitespace-nowrap">
                            {{ header }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in cohorts.cohorts" :key="row.cohort">
                        <td class="sticky left-0 z-10 bg-white dark:bg-dark-800 px-3 py-2 text-left font-medium text-gray-900 dark:text-dark-100 whitespace-nowrap">
                            <Link
                                :href="dashboardLink(row)"
                                v-tooltip="'Analyse this cohort in the dashboard'"
                                class="text-indigo-600 dark:text-indigo-400 hover:underline"
                            >
                                {{ formatLabel(row.cohort) }}
                            </Link>
                        </td>
                        <td class="px-3 py-2 text-right tabular-nums text-gray-700 dark:text-dark-300">
                            {{ row.size }}
                        </td>
                        <td
                            v-for="(value, period) in row.retention"
                            :key="period"
                            v-tooltip="cellTooltip(row, period, value)"
                            class="px-3 py-2 text-center tabular-nums rounded-sm whitespace-nowrap"
                            :class="{'text-gray-300 dark:text-dark-600': isEmpty(value)}"
                            :style="cellStyle(value)"
                        >
                            {{ formatValue(value) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else class="p-8 text-center text-gray-500 dark:text-dark-400">
            No registrations in this range yet, so there are no cohorts to show.
        </div>
    </div>
</template>
