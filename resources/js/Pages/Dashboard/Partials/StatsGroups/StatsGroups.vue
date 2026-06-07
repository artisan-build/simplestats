<script setup>
import StatsGroup from "./StatsGroup.vue";
import StatsCustomPropertyGroup from "./StatsCustomPropertyGroup.vue";

const props = defineProps({
    filters: Object,
    statsGroups: Object,
    propertyDimensions: Array,
    topReferers: Object,
    currentProject: Object,
    trackingUsageBits: Object,
});
</script>

<template>
    <div class="grid gap-4 grid-cols-1 lg:grid-cols-2">
        <div v-for="(statsGroup, statsGroupType, index) in statsGroups" :key="statsGroup.id"
             v-show="statsGroupType !== 'event' || (trackingUsageBits.events & currentProject.usage)"
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
            />
        </div>

        <!-- Custom properties: dynamic dimensions (e.g. subscription, company) -->
        <div v-if="propertyDimensions && propertyDimensions.length"
             class="flex items-stretch bg-white dark:bg-dark-800 border rounded-md"
             :class="{'lg:col-span-2': ! (trackingUsageBits.events & currentProject.usage)}"
        >
            <StatsCustomPropertyGroup
                :filters="filters"
                :propertyDimensions="propertyDimensions"
                :propertyNameSelected="$page.props.auth.user.settings?.selectedGroupTypes?.[filters.project_id]?.property"
                :currentProject="currentProject"
                :trackingUsageBits="trackingUsageBits"
            />
        </div>
    </div>
</template>
