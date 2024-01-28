<script setup>
import StatsDetailsGraphs from "@/Pages/Stats/Partials/StatsDetailsGraphs.vue";
import StatsDetailsCharts from "@/Pages/Stats/Partials/StatsDetailsCharts.vue";

const props = defineProps({
    viewMode: String,
    teamStats: Object,
});

</script>

<template>
    <div v-if="viewMode === 'simple'" class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
        <StatsDetailsGraphs :teamStats="teamStats" />
    </div>
    <div v-else class="grid gap-8 grid-cols-1 lg:grid-cols-2 -mb-4">
        <StatsDetailsCharts :teamStats="teamStats" />
    </div>
</template>
