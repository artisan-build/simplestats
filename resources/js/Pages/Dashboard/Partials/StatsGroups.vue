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
    <div class="grid gap-8 grid-cols-1 lg:grid-cols-2 4xl:grid-cols-2">
        <div v-for="(statsGroup, statsGroupType) in statsGroups" :key="statsGroup.id">
            <div class="bg-white dark:bg-gray-800 border shadow-md rounded-md" style="min-height: 560px;">
                <StatsGroup
                    :filters="filters"
                    :statsGroup="statsGroup"
                    :statsGroupType="statsGroupType"
                    :statsGroupSelected="statsGroupsSelected[statsGroupType]"
                    :topReferers="topReferers"
                />
            </div>
        </div>
    </div>
</template>
