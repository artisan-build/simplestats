<script setup>
import {computed} from 'vue';
import StatsGroup from "./StatsGroup.vue";
import StatsCustomPropertyGroup from "./StatsCustomPropertyGroup.vue";

const props = defineProps({
    filters: Object,
    statsGroups: Object,
    propertyDimensions: Array,
    hasCustomEvents: Boolean,
    topReferers: Object,
    currentProject: Object,
    usageBits: Object,
    baseRoute: {type: String, default: 'dashboard'},
    // Extra route params merged into every drill-down link (e.g. {kpi} on the metric page).
    routeParams: {type: Object, default: () => ({})},
    // Metric detail view: curated breakdown columns + the primary column to highlight/sort by.
    breakdownColumns: {type: Array, default: null},
    breakdownPrimary: {type: String, default: null},
    // Custom Events and Custom Properties never re-express the active metric (events have
    // their own fixed columns, properties only carry the visitors/reg/cr/net base set), so
    // the metric detail view opts out and shows them only on the overview.
    showCustomDimensions: {type: Boolean, default: true},
});

// Drop the event group from the dimension tables when custom dimensions are hidden, so it
// never mounts (and never fires its grouped query) on the metric detail view.
const visibleStatsGroups = computed(() => {
    if (props.showCustomDimensions) {
        return props.statsGroups;
    }

    return Object.fromEntries(
        Object.entries(props.statsGroups).filter(([type]) => type !== 'event')
    );
});
</script>

<template>
    <div class="grid gap-4 grid-cols-1 lg:grid-cols-2">
        <div v-for="(statsGroup, statsGroupType, index) in visibleStatsGroups" :key="statsGroup.id"
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
                :usageBits="usageBits"
                :breakdownColumns="breakdownColumns"
                :breakdownPrimary="breakdownPrimary"
                :baseRoute="baseRoute"
                :routeParams="routeParams"
            />
        </div>

        <!-- Custom properties: dynamic dimensions (e.g. ab_test, company) -->
        <div v-if="showCustomDimensions && propertyDimensions && propertyDimensions.length"
             class="flex items-stretch bg-white dark:bg-dark-800 border rounded-md"
             :class="{'lg:col-span-2': ! hasCustomEvents}"
        >
            <StatsCustomPropertyGroup
                :filters="filters"
                :propertyDimensions="propertyDimensions"
                :propertyNameSelected="$page.props.auth.user.settings?.selectedGroupTypes?.[filters.project_id]?.property"
                :lifetimeSelected="$page.props.auth.user.settings?.propertyLifetime?.[filters.project_id] ?? false"
                :currentProject="currentProject"
                :usageBits="usageBits"
                :breakdownPrimary="breakdownPrimary"
                :baseRoute="baseRoute"
                :routeParams="routeParams"
            />
        </div>
    </div>
</template>
