<script setup>
import {formatNumber, formatPercent} from "@/Helpers/NumberHelpers.js";
import {ArrowTrendingDownIcon, ArrowTrendingUpIcon, ScaleIcon} from "@heroicons/vue/20/solid/index.js";
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    serie: Object,
    chartInstance: Object,
});

const page = usePage();

let lastSerieData = props.serie.data[props.serie.data.length - 1];
let preSerieData = props.serie.data[props.serie.data.length - 2];

let increase = (((lastSerieData - preSerieData) / preSerieData) * 100);

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
        tailClass: 'text-red-600',
        icon: ArrowTrendingDownIcon,
    }
};

const increaseType = increaseTypes[Math.sign(increase).toString()];

</script>

<template>
    <button class="p-4 text-left border-2 border-transparent" @click="chartInstance.toggleSeries(serie.name)" :style="'background-color: '+serie.color+'40;'">
        <div class="flex justify-between text-sm font-medium text-gray-500 dark:text-gray-400">
            <div>
                {{ serie.name }}
            </div>
            <div :class="increaseType.tailClass">
                <component class="inline-block h-6 w-6 ml-2" :is="increaseType.icon" />
            </div>
        </div>
        <div class="flex justify-between items-center">
            <div class="text-xl font-semibold tracking-tight text-gray-950 dark:text-white">
                {{ serie.formatter(lastSerieData, {}, $page.props.teamCurrency) }}
            </div>
            <div class="text-lg font-bold" :class="increaseType.tailClass">
                    <span v-if="increase !== 0">
                        {{ increaseType.formatter(increase, {
                        minimumFractionDigits: (increase >= 10) ? 1 : 2,
                        maximumFractionDigits: (increase >= 10) ? 1 : 2,
                    }) }}
                    </span>
                <span v-else>stable</span>
            </div>
        </div>
    </button>
</template>

<style scoped>

</style>
