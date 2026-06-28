<script setup>
import StatsSmallChart from "@/Pages/Dashboard/Partials/Charts/StatsSmallChart.vue";

defineProps({
    sections: {type: Array, default: () => []},
    filters: Object,
});

// Columns follow the section's chart count so a section always fills its row (a
// lone chart goes full width instead of leaving empty columns).
// Full class strings, not interpolation, so Tailwind's JIT keeps them.
const gridColsClass = (count) => ({
    1: 'grid-cols-1',
    2: 'grid-cols-1 sm:grid-cols-2',
    3: 'grid-cols-1 sm:grid-cols-2 xl:grid-cols-3',
}[Math.min(count, 3)]);
</script>

<template>
    <div class="space-y-6">
        <section v-for="section in sections" :key="section.name" class="space-y-3">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-dark-400">
                {{ section.name }}
            </h2>
            <div class="grid gap-4" :class="gridColsClass(section.kpis.length)">
                <StatsSmallChart
                    v-for="kpi in section.kpis"
                    :key="kpi.key"
                    :kpi="kpi"
                    :filters="filters"
                />
            </div>
        </section>
    </div>
</template>
