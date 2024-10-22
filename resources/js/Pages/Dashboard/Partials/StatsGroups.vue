<script setup>
import StatsGroup from "@/Pages/Dashboard/Partials/StatsGroup.vue";

const props = defineProps({
    filters: Object,
    statsGroups: Object,
    topReferers: Object,
    currentProject: Object,
    trackingUsageBits: Object,
});
</script>

<template>
    <div class="grid gap-8 grid-cols-1 lg:grid-cols-2">
        <div v-for="(statsGroup, statsGroupType) in statsGroups" :key="statsGroup.id"
             class="flex items-stretch bg-white dark:bg-gray-800 border shadow-md rounded-md"
        >
            <StatsGroup
                :filters="filters"
                :statsGroup="statsGroup"
                :statsGroupType="statsGroupType"
                :statsGroupSelected="$page.props.auth.user.settings?.selectedGroupTypes?.[filters.projectId]?.[statsGroupType]"
                :topReferers="topReferers"
                :currentProject="currentProject"
                :trackingUsageBits="trackingUsageBits"
            />
        </div>
    </div>
</template>
