<script setup>
import {formatMap} from "@/Helpers/NumberHelpers.js";
import ArrowTrendingDownIcon from "bootstrap-icons/icons/arrow-down-right.svg?component";
import ArrowTrendingUpIcon from "bootstrap-icons/icons/arrow-up-right.svg?component";
import {computed} from "vue";
import InfoTooltip from "@/Components/InfoTooltip.vue";

const props = defineProps({
    label: String,
    description: {type: String, default: null},
    value: Number,
    formatter: String,
    // intra-period change in percent, or null when no meaningful comparison exists
    delta: {type: Number, default: null},
    // optional spelled-out name + explanation, shown via an info icon next to the label
    infoTitle: {type: String, default: null},
    infoText: {type: String, default: null},
});

const hasDelta = computed(() => props.delta !== null && props.delta !== 0);

const deltaClass = computed(() => {
    if (! hasDelta.value) {
        return 'text-blue-400';
    }

    return props.delta > 0 ? 'text-green-600' : 'text-red-400';
});

const deltaIcon = computed(() => {
    if (! hasDelta.value) {
        return null;
    }

    return props.delta > 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon;
});

const currencyFractionDigits = computed(() => (props.value < 10000 || props.value >= 100000) ? 2 : 0);

const deltaFractionDigits = computed(() => Math.abs(props.delta) >= 10 ? 1 : 2);
</script>

<template>
    <div class="bg-white dark:bg-dark-800 border rounded-md py-2 px-3" :title="description">
        <div class="flex justify-between items-center text-sm font-medium tracking-wider text-gray-700 dark:text-dark-400">
            <span>{{ label }}</span>
            <span class="inline-flex items-center gap-1">
                <InfoTooltip v-if="infoText" :title="infoTitle" :text="infoText" iconClass="size-5" />
            </span>
        </div>
        <div class="flex justify-between items-end gap-2 mt-1">
            <div class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                <span v-if="formatter === 'currency'">
                    {{ formatMap.currency(value, {
                        minimumFractionDigits: currencyFractionDigits,
                        maximumFractionDigits: currencyFractionDigits,
                    }, $page.props.teamCurrency) }}
                </span>
                <span v-else>{{ formatMap[formatter](value) }}</span>
            </div>
            <div v-if="hasDelta" class="text-sm font-bold whitespace-nowrap" :class="deltaClass">
                {{ formatMap.percent(delta, {
                    minimumFractionDigits: deltaFractionDigits,
                    maximumFractionDigits: deltaFractionDigits,
                }) }}
                <component v-if="deltaIcon" :is="deltaIcon" class="inline-block" :class="deltaClass" />
            </div>
        </div>
    </div>
</template>
