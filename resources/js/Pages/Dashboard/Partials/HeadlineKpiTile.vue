<script setup>
import {formatMap} from "@/Helpers/NumberHelpers.js";
import ArrowTrendingDownIcon from "bootstrap-icons/icons/arrow-down-right.svg?component";
import ArrowTrendingUpIcon from "bootstrap-icons/icons/arrow-up-right.svg?component";
import {computed} from "vue";
import InfoTooltip from "@/Components/InfoTooltip.vue";

const props = defineProps({
    label: String,
    value: Number,
    formatter: String,
    // intra-period change in percent, or null when no meaningful comparison exists
    delta: {type: Number, default: null},
    // optional spelled-out name + explanation, shown via an info icon next to the label
    infoTitle: {type: String, default: null},
    infoText: {type: String, default: null},
    // drop the card chrome (border/background/rounding) so the tile can sit flush
    // inside a parent card, e.g. fused with the chart below it
    bare: {type: Boolean, default: false},
    // place the delta right next to the value (and larger) instead of pushing it to
    // the far edge; used on the metric detail header where the number is the focus
    deltaInline: {type: Boolean, default: false},
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
    <div :class="bare ? 'pt-5 px-3' : 'bg-white dark:bg-dark-800 border rounded-md py-2 px-3'">
        <div class="flex items-center font-medium tracking-wider text-gray-700 dark:text-dark-400" :class="bare ? 'text-lg gap-2' : 'text-sm justify-between'">
            <span>{{ label }}</span>
            <span class="inline-flex items-center gap-1">
                <!-- bare: the description is shown inline below, so the icon would be redundant -->
                <InfoTooltip v-if="infoText && ! bare" :title="infoTitle" :text="infoText" iconClass="size-5" />
            </span>
        </div>
        <p v-if="bare && infoText" class="mt-0.5 text-sm text-gray-500 dark:text-dark-400">
            {{ infoText }}
        </p>
        <div class="flex mt-1" :class="deltaInline ? 'items-baseline gap-3' : 'justify-between items-end gap-2'">
            <div class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                <span v-if="formatter === 'currency'">
                    {{ formatMap.currency(value, {
                        minimumFractionDigits: currencyFractionDigits,
                        maximumFractionDigits: currencyFractionDigits,
                    }, $page.props.teamCurrency) }}
                </span>
                <span v-else>{{ formatMap[formatter](value) }}</span>
            </div>
            <div v-if="hasDelta" class="font-bold whitespace-nowrap" :class="[deltaClass, deltaInline ? 'text-lg' : 'text-sm']">
                {{ formatMap.percent(delta, {
                    minimumFractionDigits: deltaFractionDigits,
                    maximumFractionDigits: deltaFractionDigits,
                }) }}
                <component v-if="deltaIcon" :is="deltaIcon" class="inline-block" :class="deltaClass" />
            </div>
        </div>
    </div>
</template>
