<script setup>
import {ref, defineComponent, onMounted, nextTick} from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import {cent2Currency, formatDate, formatMap} from "@/Helpers/NumberHelpers.js";
import {usePage} from "@inertiajs/vue3";
import StatsIndicator from "@/Pages/Dashboard/Partials/StatsIndicator.vue";
import axios from 'axios';

const ApexChart = defineComponent(VueApexCharts);

const props = defineProps({
    chart: Object,
    chartLength: Number,
    chartIndex: Number,
});

const page = usePage();

const chartInstance = ref(null);
const series = ref(props.chart.data.series);
const categories = props.chart.data.categories;

const selectedSerieIndex = ref(Number(page.props.auth.user.settings.selectedSeries?.[props.chartIndex] ?? 0));

const formatter = () => {
    return props.chart.group[selectedSerieIndex.value].formatter;
};

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
        align: 'left',
        offsetY: 12,
        style: {
            fontSize: '17px',
            fontWeight: '600',
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
        categories: categories,
        tickAmount: 5,
        labels: {
            rotate: 0,
        },
        tooltip: {
            enabled: false,
        },
    },
    yaxis: {
        opposite: true,
        labels: {
            formatter: function (value) {
                if (formatter() === 'percent') {
                    return formatMap[formatter()](value, {
                        minimumFractionDigits: 1,
                        maximumFractionDigits: 1,
                    }, page.props.teamCurrency);
                } else if (formatter() === 'currency') {
                    return formatMap[formatter()](value, {
                        minimumFractionDigits: (cent2Currency(value) >= 1000) ? 1 : 0,
                        maximumFractionDigits: (cent2Currency(value) >= 1000) ? 1 : 0,
                    }, page.props.teamCurrency);
                } else {
                    return formatMap[formatter()](value, {
                        minimumFractionDigits: (value >= 1000) ? 1 : 0,
                        maximumFractionDigits: (value >= 1000) ? 1 : 0,
                    }, page.props.teamCurrency);
                }
            }
        }
    },
    tooltip: {
        y: {
            formatter: function(value) {
                if (formatter() === 'number') {
                    return formatMap[formatter()](value, {
                        minimumFractionDigits: (value >= 1000) ? 2 : 0,
                        maximumFractionDigits: (value >= 1000) ? 2 : 0,
                    }, page.props.teamCurrency);
                } else {
                    return formatMap[formatter()](value, {
                        minimumFractionDigits: (value === 0) ? 0 : 2,
                        maximumFractionDigits: (value === 0) ? 0 :2,
                    }, page.props.teamCurrency);
                }
            }
        }
    },
    grid: {
        show: true,
        // borderColor: '#f1f1f1',
    },
});

if (props.chartLength <= 1) {
    chartOptions.value.title.text = 'Chart';
}

const latestPeriod = categories[categories.length - 1];
const previousPeriod = categories[categories.length - 2];
const currentPeriod = formatDate(page.props.rangeCurrent, page.props.filters.rangeType);

let hasCurrentPeriod = latestPeriod === currentPeriod;

if (hasCurrentPeriod) {
    chartOptions.value.annotations = {
        xaxis: [
            {
                x: previousPeriod,
                x2: latestPeriod,
                fillColor: '#DDDDDD',
            }
        ]
    };
}

const selectSeries = (selectedSerie) => {
    selectedSerieIndex.value = selectedSerie.index;

    axios(route('stats.set-series', {
        chartIndex: props.chartIndex,
        seriesIndex: selectedSerie.index,
    }), {
        method: 'POST',
    });
};

const selectedSeries = () => {
    return series.value.filter((serie, index) => index === selectedSerieIndex.value);
};
</script>

<template>
    <div class="@container bg-white dark:bg-gray-800 border shadow-md rounded-md" :class="chart.class">
        <div class="hidden grid-cols-1 grid-cols-2 grid-cols-3"></div>
        <div :class="'grid gap-3 grid-cols-'+series.length+' pt-3 px-3'">
            <StatsIndicator
                v-for="(serie, serieIndex) in series"
                :serie="serie"
                :serieIndex="serieIndex"
                :selectedSerieIndex="selectedSerieIndex"
                :hasCurrent="hasCurrentPeriod"
                @selectSeries="selectSeries"
            />
        </div>
        <div class="pl-2 relative">
            <div v-tooltip.right="{ content: `<div class='flex flex-col space-y-4 max-w-64 p-2 text-sm'>${chart.info}</div>`, html: true }" class="absolute top-3 right-2 text-gray-400 z-10">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
            </div>
            <ApexChart
                ref="chartInstance"
                type="area"
                :height="chartOptions.chart.height"
                :options="chartOptions"
                :series="selectedSeries()" />
        </div>
    </div>
</template>
