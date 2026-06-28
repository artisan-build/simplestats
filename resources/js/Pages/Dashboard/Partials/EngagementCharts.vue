<script setup>
import StatsSmallChartGrid from "@/Pages/Dashboard/Partials/Charts/StatsSmallChartGrid.vue";
import {useChartDefinitions} from "@/Composables/useChartDefinitions.js";

const props = defineProps({
    filters: Object,
    teamStats: Object,
    currentProject: Object,
    trackingUsageBits: Object,
    engagementSeries: {type: Array, default: null},
});

const {kpiByKey} = useChartDefinitions({
    filters: props.filters,
    teamStats: props.teamStats,
    currentProject: props.currentProject,
    trackingUsageBits: props.trackingUsageBits,
    engagementSeries: props.engagementSeries,
});

const sections = [{
    name: 'Engagement',
    kpis: ['active_users', 'stickiness', 'user_churn'].map((key) => kpiByKey[key]).filter(Boolean),
}].filter((section) => section.kpis.length > 0);
</script>

<template>
    <StatsSmallChartGrid v-if="sections.length" :sections="sections" :filters="filters" />
</template>
