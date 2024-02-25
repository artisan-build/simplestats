<script setup>
import StatsChart from "@/Pages/Dashboard/Partials/StatsChart.vue";
import {formatCurrency, formatNumber, formatPercent, formatDate} from "@/Helpers/NumberHelpers.js";
import {ArrowTrendingDownIcon, ArrowTrendingUpIcon, ScaleIcon} from "@heroicons/vue/20/solid/index.js";

const props = defineProps({
    teamStats: Object,
    rangeType: String,
});

const extractStatsData4Chart = (statsCategory, fields) => {
    const data = Object.values(props.teamStats.stats).reduce((acc, item, key) => {
        // skip total and the first entry (cause the current day/week/month is not finished yet and would always down the chart)
        if (!(key === 0 || item.stats.date === 'Total')) {
            for (let field of fields) {
                if (!(field.key in acc.series)) {
                    acc.series[field.key] = {
                        name: field.key,
                        // name: '', // tooltip name empty
                        data: [],
                        visible: field.visible,
                    };
                }

                acc.series[field.key].data.push(item.stats[statsCategory][field.key]);
            }

            acc.categories.push(formatDate(item.stats.date, props.rangeType));
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

const charts = {
    per_date: [
        {
            name: 'Revenue',
            // nameShort: 'NAU',
            formatter: formatCurrency,
            data: extractStatsData4Chart('per_date', [
                {key: 'NET', visible: true},
                {key: 'GROSS', visible: false},
                {key: 'FEE', visible: false},
                {key: 'ARPDAU', visible: false},
            ]),
        },
        {
            name: 'User',
            formatter: formatNumber,
            data: extractStatsData4Chart('per_date', [
                {key: 'REG', visible: true},
                {key: 'NAU', visible: false},
                {key: 'NAPU', visible: false},
                {key: 'DAU', visible: false},
                {key: 'DAPU', visible: false},
                {key: 'DAAPU', visible: false},
            ]),
        },
    ],
    lifetime: [
        {
            name: 'Revenue',
            formatter: formatCurrency,
            data: extractStatsData4Chart('lifetime', [
                {key: 'NET', visible: true},
                {key: 'ARPU', visible: false},
                {key: 'ARPPU', visible: false},
            ]),
        },
        {
            name: 'User',
            formatter: formatNumber,
            data: extractStatsData4Chart('lifetime', [
                {key: 'REG', visible: true},
                {key: 'REG2DAU', visible: false},
                {key: 'PU', visible: false},
                {key: 'REG2PU', visible: false},
                {key: 'TAU', visible: false},
                {key: 'REG2TAU', visible: false},
            ]),
        },
    ],
};

const increaseTypes = {
    "0": {
        formatter: formatNumber,
        color: '#fdbb74',
        description: 'stable',
        tailClass: 'text-orange-600',
        icon: ScaleIcon,
    },
    "1": {
        formatter: formatPercent,
        color: '#86efac',
        description: 'increase',
        tailClass: 'text-green-600',
        icon: ArrowTrendingUpIcon,
    },
    "-1": {
        formatter: formatPercent,
        color: '#fca5a5',
        description: 'decrease',
        tailClass: 'text-red-600',
        icon: ArrowTrendingDownIcon,
    }
};
</script>

<template>
    <div class="grid gap-8 grid-cols-1">
        <StatsChart v-for="chart in charts.per_date" :chart="chart" :increaseTypes="increaseTypes" />
    </div>
    <div class="grid gap-8 grid-cols-1">
        <StatsChart v-for="chart in charts.lifetime" :chart="chart" :increaseTypes="increaseTypes" />
    </div>
</template>
