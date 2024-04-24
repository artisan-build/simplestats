<script setup>
import StatsChart from "@/Pages/Dashboard/Partials/StatsChart.vue";
import {formatDate} from "@/Helpers/NumberHelpers.js";
import { periodMap, XAU} from "@/Helpers/StringHelpers.js";
import {getKpiDefinitions} from "@/Data/KpiDefinitions.js";

const props = defineProps({
    teamStats: Object,
    rangeType: String,
});

const kpiDefs = getKpiDefinitions(props.rangeType);

const extractStatsData4Chart = (statsCategory, statsGroups) => {
    const data = Object.values(props.teamStats.stats).reduce((acc, item, key) => {
        // skip total
        let skipFirstEntry = item.stats.date === 'Total';
        if (! skipFirstEntry) {
            for (let group of statsGroups) {
                if (!(group.key in acc.series)) {
                    acc.series[group.key] = {
                        // chart specific
                        data: [],
                        name: group.title ?? group.key,
                        initialVisible: group.initialVisible,
                        color: group.color ?? '',
                        // extras...
                        key: group.key,
                        formatter: group.formatter,
                        customClass: group.customClass ?? '',
                    };
                }

                acc.series[group.key].data.push(item.stats[statsCategory][group.key]);
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
            name: periodMap[props.rangeType] + ' Revenue',
            formatter: 'currency',
            data: [
                {key: 'NET', initialVisible: true, color: '#93c5fd', customClass: 'rounded-bl-md',},
                {key: 'GROSS', initialVisible: false, color: '#60a5fa',},
                // {key: 'FEE', initialVisible: false, color: '#ffb3ba',},
                {key: 'ARPDAU', initialVisible: false, color: '#fce04c', customClass: 'rounded-br-md',},
            ],
        },
        {
            name: periodMap[props.rangeType] + ' Registrations',
            formatter: 'number',
            data: [
                {key: 'REG', initialVisible: true, color: '#87efac', customClass: 'rounded-bl-md',},
                {key: 'NAU', initialVisible: false, color: '#22c55d',},
                {key: 'NAPU', initialVisible: false, color: '#17803d', customClass: 'rounded-br-md',},
            ],
        },
        {
            name: XAU(props.rangeType, true),
            formatter: 'number',
            data: [
                {key: 'DAU', initialVisible: true, color: '#fbd44c', customClass: 'rounded-bl-md',},
                {key: 'DAPU', initialVisible: false, color: '#fbbf24',},
                {key: 'DAAPU', initialVisible: false, color: '#f59e0c', customClass: 'rounded-br-md',},
            ],
        },
    ],
    lifetime: [
        {
            name: 'Lifetime Revenue',
            formatter: 'currency',
            data: [
                {key: 'NET', initialVisible: true, color: '#93c5fd', customClass: 'rounded-bl-md',},
                // {key: 'GROSS', initialVisible: false, color: '#60a5fa',},
                {key: 'ARPU', initialVisible: false, color: '#facc14',},
                {key: 'ARPPU', initialVisible: false, color: '#ebb305', customClass: 'rounded-br-md',},
            ],
        },
        {
            name: 'Lifetime Users',
            formatter: 'number',
            data: [
                {key: 'REG', initialVisible: true, color: '#87efac', customClass: 'rounded-bl-md',},
                {key: 'PU', initialVisible: false, color: '#a6f3fc',},
                {key: 'TAU', initialVisible: false, color: '#c7d2fe', customClass: 'rounded-br-md',},
            ],
        },
        {
            name: 'Lifetime Conversion',
            formatter: 'percent',
            data: [
                {key: 'REG2DAU', initialVisible: true, color: '#49de80', customClass: 'rounded-bl-md',},
                {key: 'REG2PU', initialVisible: false, color: '#67e8f9',},
                {key: 'REG2TAU', initialVisible: false, color: '#a5b4fb', customClass: 'rounded-br-md',},
            ],
        },
    ],
};

Object.keys(charts).forEach(category => {
    charts[category].forEach(function(chartGroup) {
        let info = ``;

        chartGroup.data.forEach(function(data) {
            let kpiDef = kpiDefs[category][data.key];

            let title = (kpiDef.long) ? `${kpiDef.long} (${kpiDef.short})`: `${kpiDef.short}` ;
            info += `<div>
                        <div class="font-bold text-green-300">${title}</div>
                        <div>${kpiDef.description}</div>
                     </div>`;

            data.title = kpiDef.short;
        });


        chartGroup.info = info;

        chartGroup.data = extractStatsData4Chart(category, chartGroup.data);
    });
});
</script>

<template>
    <div class="grid gap-8 grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3">
        <StatsChart v-for="chart in charts.per_date" :chart="chart" type="per_date" />
        <StatsChart v-for="chart in charts.lifetime" :chart="chart" type="lifetime" />
    </div>
</template>
