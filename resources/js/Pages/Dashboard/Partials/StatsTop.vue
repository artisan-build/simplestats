<script setup>
import StatsGraphs from "@/Pages/Dashboard/Partials/StatsGraphs.vue";
import StatsCharts from "@/Pages/Dashboard/Partials/StatsCharts.vue";

const props = defineProps({
    viewMode: String,
    teamStats: Object,
});

</script>

<template>
    <div v-if="viewMode === 'simple'" class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
        <StatsGraphs :teamStats="teamStats" />
    </div>
    <div v-else class="grid gap-8 grid-cols-1 lg:grid-cols-2 -mb-4">
        <StatsCharts :teamStats="teamStats" />
    </div>
</template>
