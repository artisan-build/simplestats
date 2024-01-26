<script setup>
import {ref, defineComponent} from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const ApexChart = defineComponent(VueApexCharts);

const props = defineProps({
    graph: Object,
});

let increase = (((props.graph.data.series[0] - props.graph.data.series[1]) / props.graph.data.series[1]) * 100).toFixed(2);

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
    colors: [(increase > 0) ? '#86efac' : '#fca5a5'],
    dataLabels: {
        enabled: false
    },
    stroke: {
        width: 2,
        curve: 'smooth'
    },
    xaxis: {
        type: 'datetime',
        categories: props.graph.data.categories,
        labels: {
            show: false
        }
    },
    yaxis: {
        labels: {
            show: false // Y-Achsenbeschriftungen deaktivieren
        }
    },
    tooltip: {
        enabled: false // Tooltips deaktivieren
    },
    grid: {
        show: false,
    }
});
</script>

<template>
    <div class="relative p-2 pb-4 md:p-4 md:pb-8 bg-white dark:bg-gray-800 shadow border rounded-md">
        <div class="grid gap-y-2">
            <div class="flex items-center gap-x-2">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ graph.name }}</span>
            </div>

            <div class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                {{ graph.data.series[0].toString().concat(' ' + graph.unit) }}
            </div>

            <div class="flex items-center gap-x-1">
                <span class="text-sm">
                    <span class="text-green-600" v-if="increase > 0">
                        {{ increase }}% increase
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="inline-block w-5 h-5">
                            <path fill-rule="evenodd" d="M12.577 4.878a.75.75 0 0 1 .919-.53l4.78 1.281a.75.75 0 0 1 .531.919l-1.281 4.78a.75.75 0 0 1-1.449-.387l.81-3.022a19.407 19.407 0 0 0-5.594 5.203.75.75 0 0 1-1.139.093L7 10.06l-4.72 4.72a.75.75 0 0 1-1.06-1.061l5.25-5.25a.75.75 0 0 1 1.06 0l3.074 3.073a20.923 20.923 0 0 1 5.545-4.931l-3.042-.815a.75.75 0 0 1-.53-.919Z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <span class="text-red-600" v-else>
                        {{ increase }}% decrease
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="inline-block w-5 h-5">
                            <path fill-rule="evenodd" d="M1.22 5.222a.75.75 0 0 1 1.06 0L7 9.942l3.768-3.769a.75.75 0 0 1 1.113.058 20.908 20.908 0 0 1 3.813 7.254l1.574-2.727a.75.75 0 0 1 1.3.75l-2.475 4.286a.75.75 0 0 1-1.025.275l-4.287-2.475a.75.75 0 0 1 .75-1.3l2.71 1.565a19.422 19.422 0 0 0-3.013-6.024L7.53 11.533a.75.75 0 0 1-1.06 0l-5.25-5.25a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </span>
            </div>
        </div>
        <ApexChart style="margin-bottom: -2px" class="absolute inset-x-0 bottom-0 overflow-hidden rounded-b-md" type="area" :height="chartOptions.chart.height" :options="chartOptions" :series="series" />
    </div>
</template>
