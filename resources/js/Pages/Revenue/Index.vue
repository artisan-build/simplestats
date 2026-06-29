<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsFilter from "@/Pages/Dashboard/Partials/StatsFilter.vue";
import HeadlineKpiTile from "@/Pages/Dashboard/Partials/HeadlineKpiTile.vue";
import MrrMovementsChart from "@/Pages/Dashboard/Partials/MrrMovementsChart.vue";
import StatsSmallChartGrid from "@/Pages/Dashboard/Partials/Charts/StatsSmallChartGrid.vue";
import StatsGroups from "@/Pages/Dashboard/Partials/StatsGroups/StatsGroups.vue";
import SubscriptionPlanAlarm from "@/Pages/Dashboard/Partials/SubscriptionPlanAlarm.vue";
import {useChartDefinitions} from "@/Composables/useChartDefinitions.js";
import {getKpiDefinitions} from "@/Data/KpiDefinitions.js";
import {computed} from "vue";

const props = defineProps({
    teamStats: Object,
    presets: Array,
    allPresets: Array,
    hiddenPresets: Array,
    customPresets: Array,
    filters: Object,
    statsGroups: Object,
    propertyDimensions: Array,
    hasCustomEvents: Boolean,
    statsGroupNames: Object,
    topReferers: Object,
    rangeTypes: Array,
    subscriptionPlanAlarm: Array,
    usageBits: Object,
    flagBits: Object,
    comparisonTypes: Object,
    mrr: {type: Object, default: null},
    mrr_series: {type: Array, default: null},
    mrr_series_prev: {type: Array, default: null},
    mrr_movements: {type: Array, default: null},
    mrr_retention: {type: Object, default: null},
    revenue_churn: {type: Array, default: null},
    ltv: {type: Number, default: null},
});

const currentProject = props.teamStats.projects[props.filters.project_id] ?? props.teamStats.projects[0];

const kpiDefs = getKpiDefinitions(props.filters.range_type);

const {kpiByKey} = useChartDefinitions({
    filters: props.filters,
    teamStats: props.teamStats,
    currentProject,
    usageBits: props.usageBits,
    flagBits: props.flagBits,
    mrrSeries: props.mrr_series,
    mrrSeriesPrev: props.mrr_series_prev,
    revenueChurn: props.revenue_churn,
    // ARPS has no period stats on this page, so surface it from the MRR snapshot.
    includeMrrArps: true,
});

const revenueSections = [
    {name: 'Recurring revenue', keys: ['mrr', 'subscribers', 'arps']},
    {name: 'Churn & efficiency', keys: ['gross_churn', 'net_churn', 'quick_ratio']},
]
    .map((section) => ({name: section.name, kpis: section.keys.map((key) => kpiByKey[key]).filter(Boolean)}))
    .filter((section) => section.kpis.length > 0);

// Curated breakdowns for the subscriptions context: pages and devices say little
// about subscription revenue, so drop them and keep the acquisition/geography groups
// (and custom properties, e.g. plan/tier, handled separately by StatsGroups).
const subscriptionStatsGroups = computed(() => Object.fromEntries(
    Object.entries(props.statsGroups).filter(([type]) => ! ['page', 'device'].includes(type)),
));
</script>

<template>
    <AppLayout title="Subscriptions" :fullscreen="true">
        <div v-if="subscriptionPlanAlarm.length > 0" class="mb-6">
            <SubscriptionPlanAlarm :subscriptionPlanAlarm="subscriptionPlanAlarm" />
        </div>
        <div>
            <StatsFilter
                :teamStats="teamStats"
                :statsGroups="statsGroups"
                :statsGroupNames="statsGroupNames"
                :presets="presets"
                :allPresets="allPresets"
                :hiddenPresets="hiddenPresets"
                :customPresets="customPresets"
                :filters="filters"
                :rangeTypes="rangeTypes"
                :topReferers="topReferers"
                :usageBits="usageBits"
                :comparisonTypes="comparisonTypes"
                submitRoute="revenue"
            />
            <div v-if="mrr_movements?.length" class="grid gap-4">
                <div v-if="mrr" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <HeadlineKpiTile
                        :label="kpiDefs.arr.short"
                        :infoTitle="kpiDefs.arr.long"
                        :infoText="kpiDefs.arr.description"
                        :value="mrr.arr"
                        formatter="currency"
                    />
                    <HeadlineKpiTile
                        v-if="mrr_retention"
                        :label="kpiDefs.nrr.short"
                        :infoTitle="kpiDefs.nrr.long"
                        :infoText="kpiDefs.nrr.description"
                        :value="mrr_retention.nrr"
                        formatter="percent"
                    />
                    <HeadlineKpiTile
                        v-if="mrr_retention"
                        :label="kpiDefs.grr.short"
                        :infoTitle="kpiDefs.grr.long"
                        :infoText="kpiDefs.grr.description"
                        :value="mrr_retention.grr"
                        formatter="percent"
                    />
                    <HeadlineKpiTile
                        :label="kpiDefs.ltv.short"
                        :infoTitle="kpiDefs.ltv.long"
                        :infoText="kpiDefs.ltv.description"
                        :value="ltv"
                        formatter="currency"
                    />
                </div>
                <StatsSmallChartGrid v-if="revenueSections.length" :sections="revenueSections" :filters="filters" />
                <MrrMovementsChart :movements="mrr_movements" :filters="filters" />
                <StatsGroups
                    :filters="filters"
                    :statsGroups="subscriptionStatsGroups"
                    :propertyDimensions="propertyDimensions"
                    :hasCustomEvents="hasCustomEvents"
                    :topReferers="topReferers"
                    :currentProject="currentProject"
                    :usageBits="usageBits"
                    baseRoute="revenue"
                />
            </div>
            <div v-else class="bg-white dark:bg-dark-800 border rounded-md p-8 text-center text-gray-500 dark:text-dark-400">
                This project does not track subscriptions, so there is nothing to show here.
            </div>
        </div>
    </AppLayout>
</template>
