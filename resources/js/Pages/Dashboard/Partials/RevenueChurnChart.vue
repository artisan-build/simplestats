<script setup>
import {computed, defineComponent, ref} from "vue";
import VueApexCharts from 'vue3-apexcharts';
import {cent2Currency, formatCurrency, formatDate, formatMap} from "@/Helpers/NumberHelpers.js";
import {periodSingular} from "@/Helpers/StringHelpers.js";
import InfoTooltip from "@/Components/InfoTooltip.vue";
import {usePage} from "@inertiajs/vue3";

const ApexChart = defineComponent(VueApexCharts);

const props = defineProps({
    series: {type: Array, default: () => []},
    filters: Object,
});

const page = usePage();
const isDarkMode = ref(document.documentElement.classList.contains('dark'));

const periodNoun = computed(() => periodSingular[props.filters.range_type] ?? 'period');

// The series arrives newest-first (date_clear DESC); plot oldest-to-newest.
const ordered = computed(() => [...props.series].reverse());

const categories = computed(() => ordered.value.map((row) => formatDate(row.date, props.filters.range_type)));

const chartSeries = computed(() => [
    {name: 'MRR', type: 'column', data: ordered.value.map((row) => row.mrr)},
    {name: 'Gross churn', type: 'line', data: ordered.value.map((row) => row.gross_churn_rate)},
    {name: 'Net churn', type: 'line', data: ordered.value.map((row) => row.net_churn_rate)},
]);

const currency = (value) => {
    const digits = Math.abs(cent2Currency(value)) >= 1000 ? 1 : 0;

    return formatCurrency(value, {minimumFractionDigits: digits, maximumFractionDigits: digits}, page.props.teamCurrency);
};

const infoHtml = computed(() => `
    <div>
        <div class="font-bold text-green-300">MRR</div>
        <div>Active recurring revenue at the end of each ${periodNoun.value}, the base the churn rates are measured against.</div>
    </div>
    <div>
        <div class="font-bold text-green-300">Gross churn</div>
        <div>Share of the ${periodNoun.value}'s starting MRR lost to cancellations and downgrades (churn + contraction). Lower is better.</div>
    </div>
    <div>
        <div class="font-bold text-green-300">Net churn</div>
        <div>Gross churn offset by expansion and reactivation. Below zero means expansion outweighed losses (net revenue growth from existing subscribers).</div>
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
    colors: ['#34d399', '#ef4444', '#f59e0b'],
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
            seriesName: 'MRR',
            labels: {formatter: (value) => currency(value)},
        },
        {
            seriesName: ['Gross churn', 'Net churn'],
            opposite: true,
            labels: {formatter: (value) => value == null ? '' : formatMap.percent(value, {minimumFractionDigits: 0, maximumFractionDigits: 0})},
        },
        {
            seriesName: ['Gross churn', 'Net churn'],
            opposite: true,
            show: false,
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
                    ? currency(value)
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
                <h3 class="font-medium text-gray-900 dark:text-dark-100">Revenue churn</h3>
                <p class="text-sm text-gray-500 dark:text-dark-400">
                    How much recurring revenue you lose over time: the share of each {{ periodNoun }}'s starting MRR lost to cancellations and downgrades.
                    A lower gross line means fewer losses; a net line below zero means expansion outgrew churn.
                </p>
            </div>
            <InfoTooltip :html="infoHtml" placement="left" iconClass="shrink-0 size-5" />
        </div>

        <div class="p-3">
            <ApexChart type="line" :height="chartOptions.chart.height" :options="chartOptions" :series="chartSeries" />
        </div>
    </div>
</template>
