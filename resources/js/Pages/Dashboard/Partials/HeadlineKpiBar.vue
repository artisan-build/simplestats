<script setup>
import HeadlineKpiTile from "@/Pages/Dashboard/Partials/HeadlineKpiTile.vue";
import {getKpiDefinitions} from "@/Data/KpiDefinitions.js";

const props = defineProps({
    // { mrr, arr, mrr_prev } in cents, or null when the project has no payments tracking
    mrr: Object,
    // { nrr, grr, quick_ratio, ... } over the selected range, or null
    retention: {type: Object, default: null},
    filters: Object,
});

const kpiDefs = getKpiDefinitions(props.filters.range_type);

// growth of the active recurring revenue across the visible range (range start
// -> range end). Null when there was no recurring revenue at the start, so we
// don't show a meaningless or infinite percentage.
const mrrDelta = (() => {
    const prev = props.mrr.mrr_prev;

    if (! prev || prev <= 0) {
        return null;
    }

    return ((props.mrr.mrr - prev) / prev) * 100;
})();
</script>

<template>
    <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
        <div class="lg:basis-2/5">
            <div class="text-xs font-medium uppercase tracking-wider dark:text-dark-500 mb-1.5">
                Recurring revenue now
            </div>
            <div class="grid grid-cols-2 gap-4">
                <HeadlineKpiTile
                    :label="kpiDefs.mrr.short"
                    :infoTitle="kpiDefs.mrr.long"
                    :infoText="kpiDefs.mrr.description"
                    :value="mrr.mrr"
                    formatter="currency"
                    :delta="mrrDelta"
                />
                <HeadlineKpiTile
                    :label="kpiDefs.arr.short"
                    :infoTitle="kpiDefs.arr.long"
                    :infoText="kpiDefs.arr.description"
                    :value="mrr.arr"
                    formatter="currency"
                />
            </div>
        </div>

        <div v-if="retention" class="lg:basis-3/5">
            <div class="text-xs font-medium uppercase tracking-wider dark:text-dark-500 mb-1.5">
                Retention over the selected period
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <HeadlineKpiTile
                    :label="kpiDefs.nrr.short"
                    :infoTitle="kpiDefs.nrr.long"
                    :infoText="kpiDefs.nrr.description"
                    :value="retention.nrr"
                    formatter="percent"
                />
                <HeadlineKpiTile
                    :label="kpiDefs.grr.short"
                    :infoTitle="kpiDefs.grr.long"
                    :infoText="kpiDefs.grr.description"
                    :value="retention.grr"
                    formatter="percent"
                />
                <HeadlineKpiTile
                    :label="kpiDefs.quick_ratio.short"
                    :infoTitle="kpiDefs.quick_ratio.long"
                    :infoText="kpiDefs.quick_ratio.description"
                    :value="retention.quick_ratio"
                    formatter="number"
                />
            </div>
        </div>
    </div>
</template>
