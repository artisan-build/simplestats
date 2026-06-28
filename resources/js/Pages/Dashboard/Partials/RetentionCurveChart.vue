<script setup>
import {computed, defineComponent, ref} from "vue";
import VueApexCharts from 'vue3-apexcharts';
import {formatMap} from "@/Helpers/NumberHelpers.js";
import InfoTooltip from "@/Components/InfoTooltip.vue";

const ApexChart = defineComponent(VueApexCharts);

const props = defineProps({
    cohorts: Object,
    filters: Object,
});

const isDarkMode = ref(document.documentElement.classList.contains('dark'));

const periodNoun = computed(() => ({
    hours: 'Hour',
    days: 'Day',
    weeks: 'Week',
    months: 'Month',
    years: 'Year',
}[props.filters.range_type] ?? 'Period'));

// An age is only averaged while a representative share of registered users has
// actually reached it. Past that the curve becomes a survivorship-biased tail computed
// from a shrinking, older subset of cohorts, so we cut it off there (the same guard
// Amplitude/Mixpanel apply). Coverage shrinks monotonically with age, so a single
// break is correct. The first two ages are always kept so a small or single-cohort
// project still gets a curve.
const MIN_COVERAGE = 0.5;

const chart = computed(() => {
    const rows = props.cohorts.cohorts;
    const totalRegs = rows.reduce((sum, row) => sum + row.size, 0);

    const values = [];
    const labels = [];

    for (let period = 0; period < props.cohorts.period_count; period++) {
        let weighted = 0;
        let weight = 0;

        for (const row of rows) {
            const value = row.retention?.[period];

            if (value === null || value === undefined) {
                continue;
            }

            weighted += value * row.size;
            weight += row.size;
        }

        const coverage = totalRegs > 0 ? weight / totalRegs : 0;

        if (period > 1 && coverage < MIN_COVERAGE) {
            break;
        }

        values.push(weight > 0 ? Math.round((weighted / weight) * 10) / 10 : null);
        labels.push(`${periodNoun.value} ${period}`);
    }

    return {values, labels};
});

const chartSeries = computed(() => [{name: 'Avg retention', data: chart.value.values}]);

const infoHtml = computed(() => `
    <div>
        <div class="font-bold text-green-300">Average retention</div>
        <div>Size-weighted average share of cohorts still returning at each ${periodNoun.value.toLowerCase()} of age. The curve stops where fewer than half of registered users have reached that age, to avoid a survivorship-biased tail from only the oldest cohorts.</div>
    </div>
`);

const chartOptions = computed(() => ({
    chart: {
        height: 260,
        type: 'area',
        toolbar: {show: false},
        zoom: {enabled: false},
        background: isDarkMode.value ? '#262626' : '#FFFFFF',
    },
    theme: {mode: isDarkMode.value ? 'dark' : 'light'},
    colors: [isDarkMode.value ? '#00A3E0' : '#3b82f6'],
    stroke: {width: 2, curve: 'smooth'},
    fill: {type: 'gradient', gradient: {opacityFrom: 0.4, opacityTo: 0.05}},
    dataLabels: {enabled: false},
    xaxis: {
        type: 'string',
        categories: chart.value.labels,
        tickAmount: 8,
        labels: {rotate: 0},
        tooltip: {enabled: false},
    },
    yaxis: {
        min: 0,
        labels: {formatter: (value) => value == null ? '' : formatMap.percent(value, {minimumFractionDigits: 0, maximumFractionDigits: 0})},
    },
    tooltip: {
        y: {formatter: (value) => value == null ? '–' : formatMap.percent(value, {minimumFractionDigits: 1, maximumFractionDigits: 1})},
    },
    grid: {borderColor: isDarkMode.value ? '#3f3f46' : '#E5E7EB'},
}));
</script>

<template>
    <div class="bg-white dark:bg-dark-800 border dark:border-dark-700 rounded-md overflow-hidden">
        <div class="px-4 py-3 border-b dark:border-dark-700 flex items-start justify-between gap-2">
            <div>
                <h3 class="font-medium text-gray-900 dark:text-dark-100">Average retention curve</h3>
                <p class="text-sm text-gray-500 dark:text-dark-400">
                    How retention decays with cohort age, averaged across the visible cohorts. It stops where too few cohorts have reached that age, so the tail is not skewed by only the oldest ones.
                </p>
            </div>
            <InfoTooltip :html="infoHtml" placement="left" iconClass="shrink-0 size-5" />
        </div>

        <div class="p-3">
            <ApexChart type="area" :height="chartOptions.chart.height" :options="chartOptions" :series="chartSeries" />
        </div>
    </div>
</template>
