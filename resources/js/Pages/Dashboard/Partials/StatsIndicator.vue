<script setup>
import {formatMap, formatNumber, formatPercent} from "@/Helpers/NumberHelpers.js";
import {ArrowTrendingDownIcon, ArrowTrendingUpIcon} from "@heroicons/vue/20/solid/index.js";

const props = defineProps({
    serie: Object,
    serieIndex: Number,
    selectedSerieIndex: Number,
    hasCurrent: Boolean,
});

let highCurrent = props.serie.data[props.serie.data.length - 1] > props.serie.data[props.serie.data.length - 2];

let latestSerieData = props.serie.data[props.serie.data.length - 1];
let previousSerieData = (props.serie.dataPrev.length) ? props.serie.dataPrev[props.serie.dataPrev.length - 1] : props.serie.data[props.serie.data.length - 2];

// if hasCurrent we only show the prev values if at least one of the latest aren't higher
if (props.hasCurrent && ! highCurrent) {
    latestSerieData = props.serie.data[props.serie.data.length - 2];
    previousSerieData = (props.serie.dataPrev.length) ? props.serie.dataPrev[props.serie.dataPrev.length - 2] : props.serie.data[props.serie.data.length - 3];
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
        formatter: formatNumber,
        description: 'stable',
        class: 'text-blue-400',
        // icon: ScaleIcon,
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

function isSerieVisible() {
    return props.serieIndex === props.selectedSerieIndex;
}

</script>

<template>
    <button class="transition-all group py-1 px-2 sm:py-2 sm:px-3 text-left border-b-2 hover:border-b-blue-400 hover:bg-gray-100 dark:hover:bg-dark-900/50 rounded-md"
            :class="isSerieVisible() ? 'border-b-blue-400 bg-gray-100 dark:bg-dark-900/50' : 'border-white dark:border-dark-800'"
            @click="$emit('selectSeries', {index: serieIndex, data: serie})"
    >
        <div class="flex justify-between text-sm @lg:text-lg font-medium" :class="isSerieVisible() ? 'text-black dark:text-dark-200' : 'text-gray-700 dark:text-dark-400'">
            <div class="tracking-wider">
                <span class="hidden @2xl:block">{{ serie.name }}</span>
                <span class="block @2xl:hidden">{{ serie.nameSmall }}</span>
            </div>
            <div v-if="increaseType.icon" class="hidden sm:block" :class="increaseType.class">
                <component class="inline-block h-5 w-5" :is="increaseType.icon" />
            </div>
        </div>
        <div class="flex justify-between items-center">
            <div class="text-sm @lg:text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
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
            <div class="text-xs @lg:text-sm font-bold" :class="increaseType.class">
                <span v-if="increase !== 0">
                    {{ increaseType.formatter(increase, {
                    minimumFractionDigits: (Math.abs(increase) >= 10) ? 1 : 2,
                    maximumFractionDigits: (Math.abs(increase) >= 10) ? 1 : 2,
                }) }}
                </span>
<!--                <span v-else class="text-sm">stable</span>-->
            </div>
        </div>
    </button>
</template>

<style scoped>

</style>
