<script setup>
import {ref, defineComponent, onMounted} from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import {formatCurrency} from "@/Helpers/NumberHelpers.js";
import {usePage} from "@inertiajs/vue3";

const ApexChart = defineComponent(VueApexCharts);

const props = defineProps({
    chart: Object,
    increaseTypes: Object,
});

const page = usePage();

const chartInstance = ref(null);
const series = ref(props.chart.data.series);

const firstSeriesData = props.chart.data.series[0].data;

let lastSeriesData = firstSeriesData[firstSeriesData.length - 1];
let preSeriesData = firstSeriesData[firstSeriesData.length - 2];

let increase = (((lastSeriesData - preSeriesData) / preSeriesData) * 100);

if (isNaN(increase)) {
    increase = 0;
}

if (increase === Infinity) {
    increase = 100;
}

const increaseType = props.increaseTypes[Math.sign(increase).toString()];

const chartOptions = ref({
    chart: {
        height: 250,
        type: 'area',
        zoom: {
            enabled: false,
        },
        toolbar: {
            show: false,
        },
    },
    legend: {
        show: true,
        // customHTML: function(seriesName, opts) {
        //     return '<span style="color: #00E396;"><i class="fas fa-bicycle"></i> ' + seriesName + '</span>';
        // }
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
                if (props.chart.formatter.name === 'formatCurrency') {
                    return new Intl.NumberFormat('en-US', {
                        style: "currency",
                        currency: page.props.teamCurrency,
                        minimumFractionDigits: (value >= 1000) ? 1 : 0,
                        maximumFractionDigits: (value >= 1000) ? 1 : 0,
                        notation: 'compact',
                    }).format(value);
                } else {
                    return new Intl.NumberFormat('en-US', {
                        minimumFractionDigits: (value >= 1000) ? 1 : 0,
                        maximumFractionDigits: (value >= 1000) ? 1 : 0,
                        notation: 'compact',
                    }).format(value);
                }
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
        props.chart.data.series.forEach(item => !item.visible ? chartInstance.value.hideSeries(item.name) : null);
        chartInstance.value.$el.classList.remove('invisible');
    }, 0);
});

</script>

<template>

    <div class="bg-white dark:bg-gray-800 border rounded-md">
        <div class="px-4 mt-4 flex justify-between items-center">
            <h3 class="font-bold text-lg text-gray-500 dark:text-gray-400">
                {{ chart.name }}
                <span v-if="chart.nameShort" class="font-bold italic">({{ chart.nameShort }})</span>
            </h3>
            <div class="flex">
                <div class="text-2xl text-right font-semibold tracking-tight text-gray-950 dark:text-white">
                    {{chart.formatter(lastSeriesData, {}, $page.props.teamCurrency) }}
                </div>
                <div class="ml-2 flex items-center justify-end text-lg font-bold" :class="increaseType.tailClass">
                    (
                        <span v-if="increase !== 0">
                            {{ increaseType.formatter(increase, {
                                minimumFractionDigits: (increase >= 10) ? 1 : 2,
                                maximumFractionDigits: (increase >= 10) ? 1 : 2,
                            }) }}
                        </span>
                        <span v-else>stable</span>
                        <span class="hidden ml-2">{{ increaseType.description }}</span>
                        <component class="inline-block h-6 w-6 ml-2" :is="increaseType.icon" />
                    )
                </div>
            </div>
        </div>
        <div class="pl-2">
            <ApexChart
                ref="chartInstance"
                class="-my-2 invisible"
                type="area"
                :height="chartOptions.chart.height"
                :options="chartOptions"
                :series="series" />
        </div>
    </div>
</template>
