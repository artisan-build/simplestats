<script setup>
import {computed, defineComponent, ref} from "vue";
import VueApexCharts from 'vue3-apexcharts';
import {formatDate, formatMap} from "@/Helpers/NumberHelpers.js";
import {periodSingular, XAU} from "@/Helpers/StringHelpers.js";
import InfoTooltip from "@/Components/InfoTooltip.vue";

const ApexChart = defineComponent(VueApexCharts);

const props = defineProps({
    series: {type: Array, default: () => []},
    filters: Object,
});

const isDarkMode = ref(document.documentElement.classList.contains('dark'));

const periodNoun = computed(() => periodSingular[props.filters.range_type] ?? 'period');

// The series arrives newest-first (date_clear DESC); plot oldest-to-newest.
const ordered = computed(() => [...props.series].reverse());

const activeLabel = computed(() => XAU(props.filters.range_type));
const categories = computed(() => ordered.value.map((row) => formatDate(row.date, props.filters.range_type)));

const chartSeries = computed(() => [
    {name: activeLabel.value, type: 'column', data: ordered.value.map((row) => row.active_users)},
    {name: 'Stickiness', type: 'line', data: ordered.value.map((row) => row.stickiness)},
    {name: 'User Churn', type: 'line', data: ordered.value.map((row) => row.user_churn)},
]);

const infoHtml = computed(() => `
    <div>
        <div class="font-bold text-green-300">${activeLabel.value} (bars)</div>
        <div>Distinct users active in each ${periodNoun.value} (any login, including registration). Follows the selected range and granularity.</div>
    </div>
    <div>
        <div class="font-bold text-green-300">Stickiness (line)</div>
        <div>Average daily active users as a percentage of monthly active users (DAU/MAU) on a rolling 30-day window, plotted as of each ${periodNoun.value}. Higher means your active users come back more often; 20%+ is a common benchmark.</div>
    </div>
    <div>
        <div class="font-bold text-green-300">User Churn (line)</div>
        <div>Share of the preceding month's active users (30-60 days back) who did not log in again in the rolling last 30 days, as of each ${periodNoun.value}. Lower is better.</div>
    </div>
`);

const chartOptions = computed(() => ({
    chart: {
        height: 260,
        type: 'line',
        toolbar: {show: false},
        zoom: {enabled: false},
        background: isDarkMode.value ? '#262626' : '#FFFFFF',
    },
    theme: {mode: isDarkMode.value ? 'dark' : 'light'},
    colors: ['#34d399', '#2563eb', '#f59e0b'],
    stroke: {width: [0, 2, 2], curve: 'smooth'},
    fill: {opacity: [0.85, 1, 1]},
    plotOptions: {bar: {columnWidth: '55%'}},
    dataLabels: {enabled: false},
    legend: {show: true, position: 'top', horizontalAlign: 'left'},
    xaxis: {
        type: 'string',
        categories: categories.value,
        tickAmount: 6,
        labels: {rotate: 0},
        tooltip: {enabled: false},
    },
    yaxis: [
        {
            seriesName: activeLabel.value,
            labels: {formatter: (value) => formatMap.number(value, {maximumFractionDigits: 0})},
        },
        {
            seriesName: ['Stickiness', 'User Churn'],
            opposite: true,
            min: 0,
            labels: {formatter: (value) => value == null ? '' : formatMap.percent(value, {minimumFractionDigits: 0, maximumFractionDigits: 0})},
        },
        {
            seriesName: ['Stickiness', 'User Churn'],
            opposite: true,
            show: false,
            min: 0,
        },
    ],
    tooltip: {
        shared: true,
        y: {
            formatter: (value, {seriesIndex}) => {
                if (value == null) {
                    return '–';
                }

                return seriesIndex === 0
                    ? formatMap.number(value, {maximumFractionDigits: 0})
                    : formatMap.percent(value, {minimumFractionDigits: 1, maximumFractionDigits: 1});
            },
        },
    },
    grid: {borderColor: isDarkMode.value ? '#3f3f46' : '#E5E7EB'},
}));
</script>

<template>
    <div class="bg-white dark:bg-dark-800 border dark:border-dark-700 rounded-md overflow-hidden">
        <div class="px-4 py-3 border-b dark:border-dark-700 flex items-start justify-between gap-2">
            <div>
                <h3 class="font-medium text-gray-900 dark:text-dark-100">User stickiness</h3>
                <p class="text-sm text-gray-500 dark:text-dark-400">
                    How often active users come back: daily active users as a share of monthly active users (DAU/MAU) on a rolling 30-day window.
                    The bars show active users per {{ periodNoun }} across the selected range; a higher stickiness line means stickier engagement, a lower churn line means fewer users dropping off.
                </p>
            </div>
            <InfoTooltip :html="infoHtml" placement="left" iconClass="shrink-0 size-5" />
        </div>

        <div class="p-3">
            <ApexChart type="line" :height="chartOptions.chart.height" :options="chartOptions" :series="chartSeries" />
        </div>
    </div>
</template>
