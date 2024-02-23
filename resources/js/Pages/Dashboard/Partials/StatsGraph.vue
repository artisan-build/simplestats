<script setup>
import {ref, defineComponent} from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import {ArrowTrendingDownIcon, ArrowTrendingUpIcon, ScaleIcon} from "@heroicons/vue/20/solid/index.js";

const ApexChart = defineComponent(VueApexCharts);

const props = defineProps({
    graph: Object,
});

let lastSeriesData = props.graph.data.series[props.graph.data.series.length - 1];
let preSeriesData = props.graph.data.series[props.graph.data.series.length - 2];

let increase = (((lastSeriesData - preSeriesData) / preSeriesData) * 100);

if (isNaN(increase)) {
    increase = 0;
}

let absIncrease = Math.abs(increase);
if (absIncrease < 10 && absIncrease !== 0) {
    increase = increase.toFixed(2);
} else {
    increase = increase.toFixed(0);
}

const increaseTypes = {
    "0": {
        unit: '',
        color: '#fdbb74',
        description: 'stable',
        tailClass: 'text-orange-600',
        icon: ScaleIcon,
    },
    "1": {
        unit: '%',
        color: '#86efac',
        description: 'increase',
        tailClass: 'text-green-600',
        icon: ArrowTrendingUpIcon,
    },
    "-1": {
        unit: '%',
        color: '#fca5a5',
        description: 'decrease',
        tailClass: 'text-red-600',
        icon: ArrowTrendingDownIcon,
    }
};

const increaseType = increaseTypes[Math.sign(increase).toString()];

const series = ref([{
    name: props.graph.name,
    data: props.graph.data.series
}]);

const chartOptions = ref({
    chart: {
        height: 30,
        type: 'area',
        toolbar: {
            show: false
        },
        sparkline: {
            enabled: true
        }
    },
    colors: [increaseType.color],
    dataLabels: {
        enabled: false
    },
    stroke: {
        width: 2,
        curve: 'smooth'
    },
    xaxis: {
        type: 'string',
        categories: props.graph.data.categories,
        labels: {
            show: false
        }
    },
    yaxis: {
        labels: {
            show: false
        }
    },
    tooltip: {
        enabled: false
    },
    grid: {
        show: false,
    }
});
</script>

<template>
    <div class="relative p-4 pb-8 bg-white dark:bg-gray-800 shadow border rounded-md">
        <div class="grid gap-y-2">
            <div class="flex items-center gap-x-2">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ graph.name }}</span>
            </div>
            <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                {{ (graph.formatter.name === 'formatNumber')
                    ? graph.formatter(lastSeriesData, {notation: 'compact'})
                    : graph.formatter(lastSeriesData, $page.props.teamCurrency, {notation: 'compact'})
                }}
            </div>
            <div class="flex items-center gap-x-1 text-sm" :class="increaseType.tailClass">
                <span v-if="increase !== '0'">{{ Math.abs(increase).toString().concat(increaseType.unit) }}</span>
                {{ increaseType.description }}
                <component class="inline-block h-5 w-5" :is="increaseType.icon" />
            </div>
        </div>
        <ApexChart style="margin-bottom: -2px" class="absolute inset-x-0 bottom-0 overflow-hidden rounded-b-md" type="area" :height="chartOptions.chart.height" :options="chartOptions" :series="series" />
    </div>
</template>
