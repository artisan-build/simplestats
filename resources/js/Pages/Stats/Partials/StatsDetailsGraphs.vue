<script setup>

import StatsDetailsGraph from "@/Pages/Stats/Partials/StatsDetailsGraph.vue";

const props = defineProps({
    teamStats: Object,
});

const extractStatsData4Graph = (statsCategory, statsKey) => {
    const data = Object.values(props.teamStats.stats).reduce((acc, item, key) => {
        // skip total and the first entry (cause the current day/week/month is not finished yet and would always down the chart)
        if (!(key === 0 || item.stats.date === 'Total')) {
            acc.series.push(item.stats[statsCategory][statsKey]);
            acc.categories.push(item.stats.date);
        }

        return acc;
    }, { series: [], categories: [] });

    data.series.reverse();
    data.categories.reverse();

    return data;
};

const graphs = [
    {
        name: 'Regs.',
        unit: '',
        data: extractStatsData4Graph('per_date', 'REG'),
    },
    {
        name: 'DAU',
        unit: '',
        data: extractStatsData4Graph('per_date', 'DAU'),
    },
    {
        name: 'Revenue (N)',
        unit: 'EUR',
        data: extractStatsData4Graph('per_date', 'NET'),
    },
    {
        name: 'ARPU',
        unit: 'EUR',
        data: extractStatsData4Graph('lifetime', 'ARPU'),
    }
];
</script>

<template>
    <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
        <StatsDetailsGraph v-for="graph in graphs" :graph="graph" />
    </div>
</template>
