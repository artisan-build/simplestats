<script setup>
import {ref, defineComponent, onMounted} from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import {formatCurrency} from "@/Helpers/NumberHelpers.js";
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
    <div class="dark:bg-gray-800 border shadow-md rounded-md">
        <div class="pl-2">
            <ApexChart
                ref="chartInstance"
                class="invisible"
                type="area"
                :height="chartOptions.chart.height"
                :options="chartOptions"
                :series="series" />
        </div>
        <div class="grid grid-cols-3 border-t">
            <StatsButton v-for="serie in series" :serie="serie" :chartInstance="chartInstance" />
        </div>
    </div>
</template>
