<script setup>
import {formatNumber, formatPercent} from "@/Helpers/NumberHelpers.js";
import {ArrowTrendingDownIcon, ArrowTrendingUpIcon, ScaleIcon} from "@heroicons/vue/20/solid/index.js";
import {usePage} from "@inertiajs/vue3";
import {onMounted, ref} from "vue";

const props = defineProps({
    serie: Object,
    formatter: Function,
    chartInstance: Object,
});

const page = usePage();

const serieVisible = ref(props.serie.initialVisible);

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
        tailClass: 'text-red-600',
        icon: ArrowTrendingDownIcon,
    }
};

const increaseType = increaseTypes[Math.sign(increase).toString()];

const toggleChart = () => {
    serieVisible.value = ! serieVisible.value;
    props.chartInstance.toggleSeries(props.serie.name);
};

const getClass = () => {
    return  props.serie.customClass;
};

const activeStyle = 'border-color: '+props.serie.color;

const getStyle = () => {
    let style = 'background-color: '+props.serie.color+'40;';

    if (serieVisible.value) {
        style += ' '+activeStyle;
    }

    return style;
};

</script>

<template>
    <button class="p-3 text-left border-2 border-transparent hover:brightness-90" :class="getClass()" @click="toggleChart" :style="getStyle()">
        <div class="flex justify-between text-sm font-medium text-gray-500 dark:text-gray-400">
            <div>
                {{ serie.name }}
            </div>
            <div :class="increaseType.tailClass">
                <component class="inline-block h-5 w-5" :is="increaseType.icon" />
            </div>
        </div>
        <div class="flex justify-between items-center">
            <div class="text-lg font-semibold tracking-tight text-gray-950 dark:text-white">
                {{ (formatter.name === 'formatCurrency')
                    ? formatter(lastSerieData, {
                        minimumFractionDigits: (lastSerieData >= 20) ? 0 : 2,
                        maximumFractionDigits: (lastSerieData >= 20) ? 0 : 2,
                    }, $page.props.teamCurrency)
                    : formatter(lastSerieData, {}, $page.props.teamCurrency)}}
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
