<script setup>
import StatsGroup from "./StatsGroup.vue";
import StatsCustomPropertyGroup from "./StatsCustomPropertyGroup.vue";

const props = defineProps({
    filters: Object,
    statsGroups: Object,
    propertyDimensions: Array,
    hasCustomEvents: Boolean,
    topReferers: Object,
    currentProject: Object,
    trackingUsageBits: Object,
    baseRoute: {type: String, default: 'dashboard'},
    // Extra route params merged into every drill-down link (e.g. {kpi} on the metric page).
    routeParams: {type: Object, default: () => ({})},
    // Metric detail view: curated breakdown columns + the primary column to highlight/sort by.
    breakdownColumns: {type: Array, default: null},
    breakdownPrimary: {type: String, default: null},
});
</script>

<template>
    <div class="grid gap-4 grid-cols-1 lg:grid-cols-2">
        <div v-for="(statsGroup, statsGroupType, index) in statsGroups" :key="statsGroup.id"
             v-show="statsGroupType !== 'event' || hasCustomEvents"
             class="flex items-stretch bg-white dark:bg-dark-800 border rounded-md"
             :class="{'lg:col-span-2': statsGroupType === 'event' && ! propertyDimensions?.length}"
             :data-tour="index === 0 ? 'stats-groups' : undefined"
        >
            <StatsGroup
                :filters="filters"
                :statsGroup="statsGroup"
                :statsGroupType="statsGroupType"
                :statsGroupSelected="$page.props.auth.user.settings?.selectedGroupTypes?.[filters.project_id]?.[statsGroupType]"
                :topReferers="topReferers"
                :currentProject="currentProject"
                :trackingUsageBits="trackingUsageBits"
                :breakdownColumns="breakdownColumns"
                :breakdownPrimary="breakdownPrimary"
                :baseRoute="baseRoute"
                :routeParams="routeParams"
            />
        </div>

        <!-- Custom properties: dynamic dimensions (e.g. ab_test, company) -->
        <div v-if="propertyDimensions && propertyDimensions.length"
             class="flex items-stretch bg-white dark:bg-dark-800 border rounded-md"
             :class="{'lg:col-span-2': ! hasCustomEvents}"
        >
            <StatsCustomPropertyGroup
                :filters="filters"
                :propertyDimensions="propertyDimensions"
                :propertyNameSelected="$page.props.auth.user.settings?.selectedGroupTypes?.[filters.project_id]?.property"
                :lifetimeSelected="$page.props.auth.user.settings?.propertyLifetime?.[filters.project_id] ?? false"
                :currentProject="currentProject"
                :trackingUsageBits="trackingUsageBits"
                :breakdownPrimary="breakdownPrimary"
                :baseRoute="baseRoute"
                :routeParams="routeParams"
            />
        </div>
    </div>
</template>
