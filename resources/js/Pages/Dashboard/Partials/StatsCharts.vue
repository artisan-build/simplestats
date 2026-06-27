<script setup>
import StatsSmallChart from "@/Pages/Dashboard/Partials/Charts/StatsSmallChart.vue";
import CampaignKpiBar from "@/Pages/Campaigns/Partials/CampaignKpiBar.vue";
import {useChartDefinitions} from "@/Composables/useChartDefinitions.js";

const props = defineProps({
    filters: Object,
    teamStats: Object,
    currentProject: Object,
    trackingUsageBits: Object,
    mrrSeries: {type: Array, default: null},
    mrrSeriesPrev: {type: Array, default: null},
    campaignSummary: {type: Object, default: null},
});

const {
    overviewSections,
    cohortActive,
    cohortStartsAfterPeriod,
    showsRegistrations,
} = useChartDefinitions({
    filters: props.filters,
    teamStats: props.teamStats,
    currentProject: props.currentProject,
    trackingUsageBits: props.trackingUsageBits,
    mrrSeries: props.mrrSeries,
    mrrSeriesPrev: props.mrrSeriesPrev,
});

// Columns follow the section's chart count so a section always fills its row (a
// lone Visitors chart goes full width instead of leaving two empty columns).
// Full class strings, not interpolation, so Tailwind's JIT keeps them.
const gridColsClass = (count) => ({
    1: 'grid-cols-1',
    2: 'grid-cols-1 sm:grid-cols-2',
    3: 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3',
}[Math.min(count, 3)]);
</script>

<template>
    <div class="space-y-6">
        <CampaignKpiBar v-if="campaignSummary" :summary="campaignSummary" :showsRegistrations="showsRegistrations" />
        <p v-else-if="filters.campaign" class="text-sm text-gray-500 dark:text-dark-400">
            Spend-based campaign KPIs (ROAS, CAC, net profit) only apply to the full campaign. Clear the other active filters to see them.
        </p>
        <p v-if="cohortActive" class="text-sm text-gray-500 dark:text-dark-400">
            Showing only the selected registration cohort. Visitor-based metrics (Visitors, CR, ARPV) are not applicable and are hidden.
        </p>
        <p v-if="cohortStartsAfterPeriod" class="text-sm text-amber-600 dark:text-amber-400">
            The selected period ends before this cohort signed up, so there is nothing to show. Widen the period to cover the time since their registration.
        </p>

        <section v-for="(section, index) in overviewSections" :key="section.name" class="space-y-3" :data-tour="index === 0 ? 'chart-info' : undefined">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-dark-400">
                {{ section.name }}
            </h2>
            <div class="grid gap-4" :class="gridColsClass(section.kpis.length)">
                <StatsSmallChart
                    v-for="kpi in section.kpis"
                    :key="kpi.key"
                    :kpi="kpi"
                    :filters="filters"
                />
            </div>
        </section>
    </div>
</template>
