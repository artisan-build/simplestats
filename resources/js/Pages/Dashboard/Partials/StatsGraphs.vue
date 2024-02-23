<script setup>
import StatsGraph from "@/Pages/Dashboard/Partials/StatsGraph.vue";
import {formatCurrency, formatNumber} from "@/Helpers/NumberHelpers.js";

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
        formatter: formatNumber,
        data: extractStatsData4Graph('per_date', 'REG'),
    },
    {
        name: 'DAU',
        formatter: formatNumber,
        data: extractStatsData4Graph('per_date', 'DAU'),
    },
    {
        name: 'Revenue (N)',
        formatter: formatCurrency,
        data: extractStatsData4Graph('per_date', 'NET'),
    },
    {
        name: 'ARPU',
        formatter: formatCurrency,
        data: extractStatsData4Graph('lifetime', 'ARPU'),
    }
];

</script>

<template>
    <StatsGraph v-for="graph in graphs" :graph="graph" />
</template>
