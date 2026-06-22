<script setup>
import HeadlineKpiTile from "@/Pages/Dashboard/Partials/HeadlineKpiTile.vue";
import {getKpiDefinitions} from "@/Data/KpiDefinitions.js";

const props = defineProps({
    retention: Object,
    filters: Object,
});

const kpiDefs = getKpiDefinitions(props.filters.range_type);
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <HeadlineKpiTile
            :label="kpiDefs.nrr.short"
            :description="kpiDefs.nrr.description"
            :value="retention.nrr"
            formatter="percent"
        />
        <HeadlineKpiTile
            :label="kpiDefs.grr.short"
            :description="kpiDefs.grr.description"
            :value="retention.grr"
            formatter="percent"
        />
        <HeadlineKpiTile
            :label="kpiDefs.quick_ratio.short"
            :description="kpiDefs.quick_ratio.description"
            :value="retention.quick_ratio"
            formatter="number"
        />
    </div>
</template>
