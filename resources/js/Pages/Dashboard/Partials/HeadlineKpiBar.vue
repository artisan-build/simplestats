<script setup>
import HeadlineKpiTile from "@/Pages/Dashboard/Partials/HeadlineKpiTile.vue";
import {getKpiDefinitions} from "@/Data/KpiDefinitions.js";

const props = defineProps({
    // { mrr, arr, mrr_prev } in cents, or null when the project has no payments tracking
    mrr: Object,
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
    <div class="grid grid-cols-2 gap-4 sm:max-w-md">
        <HeadlineKpiTile
            :label="kpiDefs.mrr.short"
            :description="kpiDefs.mrr.description"
            :value="mrr.mrr"
            formatter="currency"
            :delta="mrrDelta"
        />
        <HeadlineKpiTile
            :label="kpiDefs.arr.short"
            :description="kpiDefs.arr.description"
            :value="mrr.arr"
            formatter="currency"
        />
    </div>
</template>
