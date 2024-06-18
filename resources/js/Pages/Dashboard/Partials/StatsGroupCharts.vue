<script setup>
import StatsGroupChart from "@/Pages/Dashboard/Partials/StatsGroupChart.vue";

const props = defineProps({
    filters: Object,
    statsGroups: Object,
});
</script>

<template>
    <div class="grid gap-8 grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3">
        <div v-for="(statsGroup, statsType) in statsGroups" :key="statsGroup.id">
            <div class="bg-white dark:bg-gray-800 border shadow-md rounded-md">
                <StatsGroupChart :statsGroup="statsGroup" :statsType="statsType" :filters="filters" />
            </div>
        </div>
    </div>
</template>
