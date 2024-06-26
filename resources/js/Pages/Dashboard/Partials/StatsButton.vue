<script setup>
import {formatNumber, formatPercent, formatMap} from "@/Helpers/NumberHelpers.js";
import {ArrowTrendingDownIcon, ArrowTrendingUpIcon, ScaleIcon} from "@heroicons/vue/20/solid/index.js";
import {ref} from "vue";

const props = defineProps({
    serie: Object,
    serieIndex: Number,
    formatter: String,
    chartInstance: Object,
    hasCurrent: Boolean,
});

let latestSerieData = props.serie.data[props.serie.data.length - 1];
let previousSerieData = props.serie.data[props.serie.data.length - 2];
//
// if (props.hasCurrent && previousSerieData > 0) {
//     let latestSerieData = props.serie.data[props.serie.data.length - 2];
//     let previousSerieData = props.serie.data[props.serie.data.length - 3];
// }

let increase = (((latestSerieData - previousSerieData) / previousSerieData) * 100);

if (isNaN(increase)) {
    increase = 0;
}

if (increase === Infinity) {
    increase = 100;
}

const increaseTypes = {
    "0": {
        f: formatNumber,
        description: 'stable',
        tailClass: 'text-yellow-600',
        icon: ScaleIcon,
    },
    "1": {
        formatter: formatPercent,
        description: 'increase',
        tailClass: 'text-green-600',
        icon: ArrowTrendingUpIcon,
    },
    "-1": {
        formatter: formatPercent,
        description: 'decrease',
        tailClass: 'text-red-500',
        icon: ArrowTrendingDownIcon,
    }
};

const increaseType = increaseTypes[Math.sign(increase).toString()];

const isSerieVisible = () => {
    if (! props.chartInstance?.chart) return props.serie.initialVisible;
    return ! props.chartInstance.chart.w.globals.collapsedSeries.some((collapsedSerie) => collapsedSerie.index === props.serieIndex)
}

</script>

<template>
    <button class="group p-2 sm:p-3 text-left border-r last:border-r-0 border-t-2 hover:border-t-blue-300" :class="isSerieVisible() ? 'border-t-blue-300' : 'border-t-white'" @click="$emit('selectSeries', {index: serieIndex, data: serie})">
        <div class="flex justify-between text-sm font-medium text-gray-700 dark:text-gray-400 group-hover:underline" :class="{'underline': isSerieVisible()}">
            <div class="tracking-wider">
                {{ serie.name }}
            </div>
            <div class="hidden sm:block" :class="increaseType.tailClass">
                <component class="inline-block h-5 w-5" :is="increaseType.icon" />
            </div>
        </div>
        <div class="flex justify-between items-center">
            <div class="text-md sm:text-lg font-semibold tracking-tight text-gray-950 dark:text-white">
                <span v-if="formatter === 'percent'">
                    {{ formatMap[formatter](latestSerieData, {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    }, $page.props.teamCurrency) }}
                </span>
                <span v-else>
                    {{ formatMap[formatter](latestSerieData, {
                        minimumFractionDigits: (latestSerieData >= 1000) ? 2 : 0,
                        maximumFractionDigits: (latestSerieData >= 1000) ? 2 : 0,
                    }, $page.props.teamCurrency) }}
                </span>
            </div>
            <div class="text-sm font-bold" :class="increaseType.tailClass">
                    <span v-if="increase !== 0">
                        {{ increaseType.formatter(increase, {
                        minimumFractionDigits: (Math.abs(increase) >= 10) ? 1 : 2,
                        maximumFractionDigits: (Math.abs(increase) >= 10) ? 1 : 2,
                    }) }}
                    </span>
                <span v-else class="text-sm">stable</span>
            </div>
        </div>
    </button>
</template>

<style scoped>

</style>
