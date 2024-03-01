<script setup>
import {ref, defineComponent, onMounted} from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import {cent2Currency, formatCurrency} from "@/Helpers/NumberHelpers.js";
import {usePage} from "@inertiajs/vue3";
import StatsButton from "@/Pages/Dashboard/Partials/StatsButton.vue";

const ApexChart = defineComponent(VueApexCharts);

const props = defineProps({
    chart: Object,
});

const page = usePage();

const chartInstance = ref(null);
const series = ref(props.chart.data.series);

const chartOptions = ref({
    chart: {
        height: 250,
        type: 'area',
        zoom: {
            enabled: false,
        },
        toolbar: {
            show: false,
            offsetY: 12,
        },
    },
    title: {
        text: props.chart.name,
        align: 'center',
        offsetY: 12,
        style: {
            fontSize: '18px',
            fontWeight: '500',
            fontFamily: 'Figtree, ui-sans-serif',
            color: '#374151'
        },
    },
    legend: {
        show: false,
    },
    // colors: [increaseType.color],
    dataLabels: {
        enabled: false
    },
    stroke: {
        width: 2,
        curve: 'smooth',
        // curve: 'straight',
    },
    xaxis: {
        type: 'string',
        categories: props.chart.data.categories,
        tooltip: {
            enabled: false,
        },
    },
    yaxis: {
        opposite: true,
        labels: {
            formatter: function (value) {
                value = cent2Currency(value);
                // en-US format, so that we get compact values very early
                if (props.chart.formatter.name === 'formatCurrency') {
                    return new Intl.NumberFormat('en-US', {
                        style: "currency",
                        currency: page.props.teamCurrency,
                        minimumFractionDigits: (value >= 1000) ? 1 : 0,
                        maximumFractionDigits: (value >= 1000) ? 1 : 0,
                        notation: 'compact',
                    }).format(value);
                } else if (props.chart.formatter.name === 'formatNumber') {
                    return new Intl.NumberFormat('en-US', {
                        minimumFractionDigits: (value >= 1000) ? 1 : 0,
                        maximumFractionDigits: (value >= 1000) ? 1 : 0,
                        notation: 'compact',
                    }).format(value);
                } else if (props.chart.formatter.name === 'formatPercent') {
                    return props.chart.formatter(value, {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0,
                    }, page.props.teamCurrency);
                }

                return props.chart.formatter(value, {}, page.props.teamCurrency);
            }
        }
    },
    tooltip: {
        y: {
            formatter: function(value) {
                return props.chart.formatter(value, {}, page.props.teamCurrency);
            }
        }
    },
    grid: {
        show: true,
        // borderColor: '#f1f1f1',
    }
});

onMounted(() => {
    setTimeout(() => {
        props.chart.data.series.forEach(serie => !serie.initialVisible ? chartInstance.value.hideSeries(serie.name) : null);
        chartInstance.value.$el.classList.remove('invisible');
    }, 0);
});
</script>

<template>
    <div class="dark:bg-gray-800 border shadow-md rounded-md">
        <div class="pl-2 relative">
            <div v-tooltip.right="{ content: `<div class='flex flex-col space-y-4 max-w-64 p-2 text-sm'>${chart.info}</div>`, html: true }" class="absolute top-1.5 right-1.5 text-gray-400 z-10">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
            </div>
            <ApexChart
                ref="chartInstance"
                class="invisible"
                type="area"
                :height="chartOptions.chart.height"
                :options="chartOptions"
                :series="series" />
        </div>
        <div class="grid grid-cols-3 border-t">
            <StatsButton v-for="serie in series"
                         :serie="serie"
                         :formatter="chart.formatter"
                         :chartInstance="chartInstance"
            />
        </div>
    </div>
</template>
