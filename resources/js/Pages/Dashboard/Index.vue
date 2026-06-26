<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsFilter from "@/Pages/Dashboard/Partials/StatsFilter.vue";
import StatsCharts from "@/Pages/Dashboard/Partials/StatsCharts.vue";
import CohortRetentionTable from "@/Pages/Dashboard/Partials/CohortRetentionTable.vue";
import EngagementChart from "@/Pages/Dashboard/Partials/EngagementChart.vue";
import StatsGroups from "@/Pages/Dashboard/Partials/StatsGroups/StatsGroups.vue";
import FreePlanWarning from "@/Pages/Dashboard/Partials/FreePlanWarning.vue";
import ReadDocsWarning from "@/Pages/Dashboard/Partials/ReadDocsWarning.vue";
import SubscriptionPlanAlarm from "@/Pages/Dashboard/Partials/SubscriptionPlanAlarm.vue";
import {onMounted, onUnmounted, ref} from "vue";
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

const isScrolled = ref(false)

const handleScroll = () => {
    isScrolled.value = window.scrollY > 0
}

onMounted(() => {
    window.addEventListener('scroll', handleScroll)
    if (! props.showRetention) {
        startTour();
    }
})

// Filtering and drill-down links must stay on the current route (dashboard or
// retention), so everything navigation-related is keyed off this base route.
const baseRoute = props.showRetention ? 'retention' : 'dashboard';

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll)
})


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
            <div class="sticky top-0 px-4 py-2 -mx-4 -my-2 mb-2 bg-gray-50 dark:bg-dark-900 z-20" :class="{'border-b dark:border-dark-700 shadow-sm': isScrolled}">
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
            </div>
            <div class="grid grid-cols-1 gap-4">
                <div class="min-w-0">
                    <div v-if="showRetention && retention_cohorts" class="space-y-4">
                        <EngagementChart
                            v-if="engagement_series && engagement_series.length"
                            :series="engagement_series"
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
                <div>
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
