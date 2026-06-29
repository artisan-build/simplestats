<script setup>
import {defineComponent, ref} from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import {cent2Currency, formatCurrency, formatDate} from "@/Helpers/NumberHelpers.js";
import {usePage} from "@inertiajs/vue3";
import InfoTooltip from "@/Components/InfoTooltip.vue";

const ApexChart = defineComponent(VueApexCharts);

const props = defineProps({
    movements: Array,
    filters: Object,
});

const infoHtml = `
    <div>
        <div class="font-bold text-green-300">New</div>
        <div>Recurring revenue from subscribers paying for the first time.</div>
    </div>
    <div>
        <div class="font-bold text-green-300">Reactivation</div>
        <div>Recurring revenue from subscribers who had churned earlier and came back.</div>
    </div>
    <div>
        <div class="font-bold text-green-300">Expansion</div>
        <div>Added recurring revenue from existing subscribers upgrading to a higher amount.</div>
    </div>
    <div>
        <div class="font-bold text-green-300">Contraction</div>
        <div>Lost recurring revenue from existing subscribers downgrading to a lower amount.</div>
    </div>
    <div>
        <div class="font-bold text-green-300">Churn</div>
        <div>Lost recurring revenue from subscribers who cancelled entirely.</div>
    </div>
`;

const page = usePage();
const isDarkMode = ref(document.documentElement.classList.contains('dark'));

// The backend returns newest first; charts read oldest -> newest left to right.
const ordered = [...props.movements].reverse();

const categories = ordered.map(item => formatDate(item.date, props.filters.range_type));

// Contraction and churn are revenue lost, so they stack below the zero line.
const series = [
    {name: 'New', data: ordered.map(item => item.new)},
    {name: 'Reactivation', data: ordered.map(item => item.reactivation)},
    {name: 'Expansion', data: ordered.map(item => item.expansion)},
    {name: 'Contraction', data: ordered.map(item => -item.contraction)},
    {name: 'Churn', data: ordered.map(item => -item.churn)},
];

const currency = (value) => formatCurrency(value, {
    minimumFractionDigits: (Math.abs(cent2Currency(value)) >= 1000) ? 1 : 0,
    maximumFractionDigits: (Math.abs(cent2Currency(value)) >= 1000) ? 1 : 0,
}, page.props.teamCurrency);

const chartOptions = ref({
    chart: {
        type: 'bar',
        height: 250,
        stacked: true,
        toolbar: {show: false},
        zoom: {enabled: false},
        background: isDarkMode.value ? '#262626' : '#FFFFFF',
    },
    colors: ['#4ade80', '#2dd4bf', '#60a5fa', '#fb923c', '#f87171'],
    theme: {mode: isDarkMode.value ? 'dark' : 'light'},
    plotOptions: {
        bar: {columnWidth: '70%'},
    },
    dataLabels: {enabled: false},
    legend: {show: true, position: 'top', horizontalAlign: 'left'},
    xaxis: {
        type: 'string',
        categories: categories,
        tickAmount: 5,
        labels: {rotate: 0},
        tooltip: {enabled: false},
    },
    yaxis: {
        opposite: true,
        labels: {
            formatter: (value) => currency(value),
        },
    },
    tooltip: {
        y: {
            formatter: (value) => currency(value),
        },
    },
    grid: {
        show: true,
        borderColor: isDarkMode.value ? '#3f3f46' : '#E5E7EB',
    },
});
</script>

<template>
    <div class="bg-white dark:bg-dark-800 border dark:border-dark-700 rounded-md overflow-hidden">
        <div class="px-4 py-3 border-b dark:border-dark-700 flex items-start justify-between gap-2">
            <div>
                <h3 class="font-medium text-gray-900 dark:text-dark-100">MRR movements</h3>
                <p class="text-sm text-gray-500 dark:text-dark-400">
                    How recurring revenue changed each period: gains from new, reactivated and expanding subscribers stack above the line, losses from contraction and churn below it.
                </p>
            </div>
            <InfoTooltip :html="infoHtml" placement="left" iconClass="shrink-0 size-5" />
        </div>

        <div class="p-3 pl-2 pr-0.5">
            <ApexChart
                type="bar"
                :height="chartOptions.chart.height"
                :options="chartOptions"
                :series="series"
            />
        </div>
    </div>
</template>
