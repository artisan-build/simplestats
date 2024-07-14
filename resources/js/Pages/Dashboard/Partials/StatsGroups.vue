<script setup>
import StatsGroup from "@/Pages/Dashboard/Partials/StatsGroup.vue";

const props = defineProps({
    filters: Object,
    statsGroups: Object,
    statsGroupsSelected: Object,
    topReferers: Object,
});
</script>

<template>
    <div class="grid gap-8 grid-cols-1 xl:grid-cols-2 2xl:grid-cols-3 3xl:grid-cols-4">
        <div v-for="(statsGroup, statsGroupType) in statsGroups" :key="statsGroup.id"
             class="flex items-stretch bg-white dark:bg-gray-800 border shadow-md rounded-md"
        >
            <StatsGroup
                :filters="filters"
                :statsGroup="statsGroup"
                :statsGroupType="statsGroupType"
                :statsGroupSelected="statsGroupsSelected[statsGroupType]"
                :topReferers="topReferers"
            />
        </div>
    </div>
</template>
