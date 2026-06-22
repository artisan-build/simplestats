<script setup>
import {defineComponent, ref} from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import {cent2Currency, formatCurrency, formatDate} from "@/Helpers/NumberHelpers.js";
import {usePage} from "@inertiajs/vue3";

const ApexChart = defineComponent(VueApexCharts);

const props = defineProps({
    movements: Array,
    filters: Object,
});

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
    colors: ['#22c55e', '#14b8a6', '#3b82f6', '#f97316', '#ef4444'],
    title: {
        text: 'MRR Movements',
        align: 'left',
        offsetY: 12,
        style: {
            fontSize: '17px',
            fontWeight: '600',
            fontFamily: 'Figtree, ui-sans-serif',
            color: isDarkMode.value ? '#AAAAAA' : '#374151',
        },
    },
    theme: {mode: isDarkMode.value ? 'dark' : 'light'},
    plotOptions: {
        bar: {columnWidth: '70%'},
    },
    dataLabels: {enabled: false},
    legend: {show: true, position: 'top', horizontalAlign: 'right'},
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
    <div class="bg-white dark:bg-dark-800 border rounded-md">
        <div class="pl-2 pr-0.5 relative">
            <ApexChart
                type="bar"
                :height="chartOptions.chart.height"
                :options="chartOptions"
                :series="series"
            />
        </div>
    </div>
</template>
