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
    highCurrent: Boolean,
});

let latestSerieData = props.serie.data[props.serie.data.length - 1];
let previousSerieData = props.serie.data[props.serie.data.length - 2];

// if hasCurrent we only show the prev values if at least one of the latest aren't higher
if (props.hasCurrent && ! props.highCurrent) {
    latestSerieData = props.serie.data[props.serie.data.length - 2];
    previousSerieData = props.serie.data[props.serie.data.length - 3];
}

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
        class: 'text-yellow-600',
        icon: ScaleIcon,
    },
    "1": {
        formatter: formatPercent,
        description: 'increase',
        class: 'text-green-600',
        icon: ArrowTrendingUpIcon,
    },
    "-1": {
        formatter: formatPercent,
        description: 'decrease',
        class: 'text-red-400',
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
    <button class="group py-1 px-2 sm:py-2 sm:px-3 text-left border-b-2 hover:border-b-blue-300 hover:bg-gray-100 rounded-md"
            :class="isSerieVisible() ? 'border-b-blue-300 bg-gray-100' : 'border-white'"
            @click="$emit('selectSeries', {index: serieIndex, data: serie})"
    >
        <div class="flex justify-between text-lg font-medium" :class="isSerieVisible() ? 'text-black dark:text-gray-200' : 'text-gray-700 dark:text-gray-400'">
            <div class="tracking-wider">
                <span class="hidden @2xl:block">{{ serie.name }}</span>
                <span class="block @2xl:hidden">{{ serie.nameSmall }}</span>
            </div>
            <div class="hidden sm:block" :class="increaseType.class">
                <component class="inline-block h-5 w-5" :is="increaseType.icon" />
            </div>
        </div>
        <div class="flex justify-between items-center">
            <div class="text-base @md:text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                <span v-if="serie.formatter === 'percent'">
                    {{
                         formatMap[serie.formatter](serie.indicator, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                        })
                    }}
                </span>
                <span v-else-if="serie.formatter === 'currency'">
                    {{
                        formatMap[serie.formatter](serie.indicator, {
                            minimumFractionDigits: (serie.indicator < 10000 || serie.indicator >= 100000) ? 2 : 0,
                            maximumFractionDigits: (serie.indicator < 10000 || serie.indicator >= 100000) ? 2 : 0,
                        }, $page.props.teamCurrency)
                    }}
                </span>
                <span v-else>
                    {{
                        formatMap[serie.formatter](serie.indicator, {
                            minimumFractionDigits: (serie.indicator >= 1000) ? 2 : 0,
                            maximumFractionDigits: (serie.indicator >= 1000) ? 2 : 0,
                        })
                    }}
                </span>
            </div>
            <div class="text-sm font-bold" :class="increaseType.class">
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
