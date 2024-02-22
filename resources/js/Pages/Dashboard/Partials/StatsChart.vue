<script setup>
import {ref, defineComponent, onMounted} from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const ApexChart = defineComponent(VueApexCharts);

const props = defineProps({
    chart: Object,
});

const chartInstance = ref(null);

const series = ref(props.chart.data.series);

const chartOptions = ref({
    chart: {
        height: 300,
        type: 'area',
        zoom: {
            enabled: false,
        }
    },
    title: {
        text: props.chart.name,
        align: 'center',
        style: {
            fontSize: '14px',
            fontWeight: '500',
            fontFamily: 'Figtree, ui-sans-serif',
            color: '#6e6e6e'
        },
    },
    // colors: [increaseType.color],
    dataLabels: {
        enabled: false
    },
    stroke: {
        width: 2,
        curve: 'smooth'
    },
    xaxis: {
        type: 'string',
        categories: props.chart.data.categories,
    },
    yaxis: {
        opposite: true,
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
    <ApexChart ref="chartInstance" class="invisible" type="area" :height="chartOptions.chart.height" :options="chartOptions" :series="series" />
</template>
