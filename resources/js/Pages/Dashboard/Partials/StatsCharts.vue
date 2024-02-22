<script setup>
import StatsChart from "@/Pages/Dashboard/Partials/StatsChart.vue";

const props = defineProps({
    teamStats: Object,
});

const extractStatsData4Graph = (statsCategory, fields) => {
    const data = Object.values(props.teamStats.stats).reduce((acc, item, key) => {
        // skip total and the first entry (cause the current day/week/month is not finished yet and would always down the chart)
        if (!(key === 0 || item.stats.date === 'Total')) {
            for (let field of fields) {
                if (!(field.key in acc.series)) {
                    acc.series[field.key] = {
                        name: field.key,
                        data: [],
                        visible: field.visible,
                    };
                }

                acc.series[field.key].data.push(item.stats[statsCategory][field.key]);
            }

            acc.categories.push(item.stats.date);
        }

        return acc;
    }, { series: {}, categories: [] });

    data.series = Object.values(data.series).map(item => ({
        ...item,
        data: item.data.reverse()
    }));

    data.categories.reverse();

    return data;
};

const charts = [
    {
        name: 'By Date',
        data: extractStatsData4Graph('per_date', [
            {key: 'REG', visible: true},
            {key: 'NAU', visible: false},
            {key: 'NAPU', visible: false},
            {key: 'DAU', visible: false},
            {key: 'DAPU', visible: false},
            {key: 'DAAPU', visible: false},
            {key: 'GROSS', visible: false},
            {key: 'NET', visible: false},
            {key: 'FEE', visible: false},
            {key: 'ARPDAU', visible: false}
        ]),
    },
    {
        name: 'Lifetime',
        data: extractStatsData4Graph('lifetime', [
            {key: 'REG', visible: true},
            {key: 'REG2DAU', visible: false},
            {key: 'PU', visible: false},
            {key: 'REG2PU', visible: false},
            {key: 'TAU', visible: false},
            {key: 'REG2TAU', visible: false},
            {key: 'ARPU', visible: false},
            {key: 'ARPPU', visible: false},
            {key: 'NET', visible: false},
        ]),
    },
];
</script>

<template>
    <StatsChart v-for="chart in charts" :chart="chart" />
</template>
