<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsFilter from "@/Pages/Dashboard/Partials/StatsFilter.vue";
import StatsCharts from "@/Pages/Dashboard/Partials/StatsCharts.vue";
import CohortRetentionTable from "@/Pages/Dashboard/Partials/CohortRetentionTable.vue";
import RetentionCurveChart from "@/Pages/Dashboard/Partials/RetentionCurveChart.vue";
import EngagementCharts from "@/Pages/Dashboard/Partials/EngagementCharts.vue";
import StatsGroups from "@/Pages/Dashboard/Partials/StatsGroups/StatsGroups.vue";
import FreePlanWarning from "@/Pages/Dashboard/Partials/FreePlanWarning.vue";
import ReadDocsWarning from "@/Pages/Dashboard/Partials/ReadDocsWarning.vue";
import SubscriptionPlanAlarm from "@/Pages/Dashboard/Partials/SubscriptionPlanAlarm.vue";
import {onMounted} from "vue";
import {useOnboardingTour} from "@/Composables/useOnboardingTour.js";

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
    freePlanWarning: Object,
    readDocsWarning: Object,
    subscriptionPlanAlarm: Array,
    trackingUsageBits: Object,
    comparisonTypes: Object,
    mrr_series: {type: Array, default: null},
    mrr_series_prev: {type: Array, default: null},
    campaign_summary: {type: Object, default: null},
    engagement_series: {type: Array, default: null},
    showRetention: {type: Boolean, default: false},
    retention_cohorts: {type: Object, default: null},
});

const {startTour} = useOnboardingTour();

onMounted(() => {
    if (! props.showRetention) {
        startTour();
    }
})

// Filtering and drill-down links must stay on the current route (dashboard or
// retention), so everything navigation-related is keyed off this base route.
const baseRoute = props.showRetention ? 'retention' : 'dashboard';

const currentProject = props.teamStats.projects[props.filters.project_id] ?? props.teamStats.projects[0];
</script>

<template>
    <AppLayout title="Dashboard" :fullscreen="true">
<!--        <template #header>Dashboard</template>-->

        <div v-if="freePlanWarning.show">
            <FreePlanWarning />
        </div>
        <div v-if="readDocsWarning.show">
            <ReadDocsWarning />
        </div>
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
                :submitRoute="baseRoute"
            />
            <div class="grid grid-cols-1 gap-4">
                <div class="min-w-0">
                    <div v-if="showRetention && retention_cohorts" class="space-y-4">
                        <EngagementCharts
                            v-if="engagement_series && engagement_series.length"
                            :filters="filters"
                            :teamStats="teamStats"
                            :currentProject="currentProject"
                            :trackingUsageBits="trackingUsageBits"
                            :engagementSeries="engagement_series"
                        />
                        <RetentionCurveChart
                            v-if="retention_cohorts.cohorts.length"
                            :cohorts="retention_cohorts"
                            :filters="filters"
                        />
                        <CohortRetentionTable
                            :cohorts="retention_cohorts"
                            :filters="filters"
                        />
                    </div>
                    <StatsCharts
                        v-else
                        :filters="filters"
                        :teamStats="teamStats"
                        :currentProject="currentProject"
                        :trackingUsageBits="trackingUsageBits"
                        :mrrSeries="mrr_series"
                        :mrrSeriesPrev="mrr_series_prev"
                        :campaignSummary="campaign_summary"
                    />
                </div>
                <div class="space-y-3">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-dark-400">
                        Breakdown
                    </h2>
                    <StatsGroups
                        :filters="filters"
                        :statsGroups="statsGroups"
                        :propertyDimensions="propertyDimensions"
                        :hasCustomEvents="hasCustomEvents"
                        :topReferers="topReferers"
                        :currentProject="currentProject"
                        :trackingUsageBits="trackingUsageBits"
                        :baseRoute="baseRoute"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
