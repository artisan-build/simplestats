<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsFilter from "@/Pages/Dashboard/Partials/StatsFilter.vue";
import HeadlineKpiBar from "@/Pages/Dashboard/Partials/HeadlineKpiBar.vue";
import MrrMovementsChart from "@/Pages/Dashboard/Partials/MrrMovementsChart.vue";
import RevenueChurnChart from "@/Pages/Dashboard/Partials/RevenueChurnChart.vue";
import StatsGroups from "@/Pages/Dashboard/Partials/StatsGroups/StatsGroups.vue";
import SubscriptionPlanAlarm from "@/Pages/Dashboard/Partials/SubscriptionPlanAlarm.vue";
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
    trackingUsageBits: Object,
    comparisonTypes: Object,
    mrr: {type: Object, default: null},
    mrr_movements: {type: Array, default: null},
    mrr_retention: {type: Object, default: null},
    revenue_churn: {type: Array, default: null},
});

const currentProject = props.teamStats.projects[props.filters.project_id] ?? props.teamStats.projects[0];

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
                :trackingUsageBits="trackingUsageBits"
                :comparisonTypes="comparisonTypes"
                submitRoute="revenue"
            />
            <div v-if="mrr_movements?.length" class="grid gap-4">
                <HeadlineKpiBar v-if="mrr" :mrr="mrr" :retention="mrr_retention" :filters="filters" />
                <MrrMovementsChart :movements="mrr_movements" :filters="filters" />
                <RevenueChurnChart v-if="revenue_churn?.length" :series="revenue_churn" :filters="filters" />
                <StatsGroups
                    :filters="filters"
                    :statsGroups="subscriptionStatsGroups"
                    :propertyDimensions="propertyDimensions"
                    :hasCustomEvents="hasCustomEvents"
                    :topReferers="topReferers"
                    :currentProject="currentProject"
                    :trackingUsageBits="trackingUsageBits"
                    baseRoute="revenue"
                />
            </div>
            <div v-else class="bg-white dark:bg-dark-800 border rounded-md p-8 text-center text-gray-500 dark:text-dark-400">
                This project does not track subscriptions, so there is nothing to show here.
            </div>
        </div>
    </AppLayout>
</template>
