<script setup>
import StatsGroup from "@/Pages/Dashboard/Partials/StatsGroup.vue";

const props = defineProps({
    filters: Object,
    statsGroups: Object,
    statsGroupsSelected: Object,
});
</script>

<template>
    <div class="grid gap-8 grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3">
        <div v-for="(statsGroup, statsGroupType) in statsGroups" :key="statsGroup.id">
            <div class="bg-white dark:bg-gray-800 border shadow-md rounded-md">
                <StatsGroup :statsGroup="statsGroup" :statsGroupType="statsGroupType" :statsGroupSelected="statsGroupsSelected[statsGroupType]" :filters="filters" />
            </div>
        </div>
    </div>
</template>
