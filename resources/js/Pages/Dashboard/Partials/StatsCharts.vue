<script setup>
import StatsChart from "@/Pages/Dashboard/Partials/StatsChart.vue";
import {formatDate} from "@/Helpers/NumberHelpers.js";
import { periodMap, XAU} from "@/Helpers/StringHelpers.js";
import {getKpiDefinitions} from "@/Data/KpiDefinitions.js";
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    rangeType: String,
    teamStats: Object,
});

const page = usePage();

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
            name: periodMap[props.rangeType] + ' Conversion',
            formatter: 'number',
            data: [
                {key: 'VISITOR', initialVisible: false, color: '#93c5fd', formatter: 'number',},
                {key: 'REG', initialVisible: false, color: '#93c5fd', formatter: 'number',},
                {key: 'CR', initialVisible: false, color: '#93c5fd', formatter: 'percent',},
            ],
        },
        {
            name: XAU(props.rangeType, true),
            formatter: 'number',
            data: [
                {key: 'DAU', initialVisible: false, color: '#93c5fd', formatter: 'number',},
                {key: 'DAPU', initialVisible: false, color: '#93c5fd', formatter: 'number',},
                {key: 'DAAPU', initialVisible: false, color: '#93c5fd', formatter: 'number',},
            ],
        },
        {
            name: periodMap[props.rangeType] + ' Revenue',
            formatter: 'currency',
            data: [
                {key: 'NET', initialVisible: false, color: '#93c5fd', formatter: 'currency',},
                {key: 'FEE', initialVisible: false, color: '#93c5fd', formatter: 'currency',},
                {key: 'GROSS', initialVisible: false, color: '#93c5fd', formatter: 'currency',},
            ],
        },
        {
            name: periodMap[props.rangeType] + ' Growth',
            formatter: 'number',
            data: [
                {key: 'ARPDAU', initialVisible: false, color: '#93c5fd', formatter: 'currency',},
                {key: 'NAU', initialVisible: false, color: '#93c5fd', formatter: 'number',},
                {key: 'NAPU', initialVisible: false, color: '#93c5fd', formatter: 'number',},
            ],
        },
    ],
    lifetime: [
        {
            name: 'Lifetime Conversion',
            formatter: 'number',
            data: [
                {key: 'VISITOR', initialVisible: false, color: '#93c5fd', formatter: 'number',},
                {key: 'REG', initialVisible: false, color: '#93c5fd', formatter: 'number',},
                {key: 'CR', initialVisible: false, color: '#93c5fd', formatter: 'percent',},
            ],
        },
        {
            name: 'Lifetime Engagement',
            formatter: 'percent',
            data: [
                {key: 'REG2DAU', initialVisible: false, color: '#93c5fd', formatter: 'percent',},
                {key: 'REG2PU', initialVisible: false, color: '#93c5fd', formatter: 'percent',},
                {key: 'REG2TAU', initialVisible: false, color: '#93c5fd', formatter: 'percent',},
            ],
        },
        {
            name: 'Lifetime Revenue',
            formatter: 'currency',
            data: [
                {key: 'NET', initialVisible: false, color: '#93c5fd', formatter: 'currency',},
                {key: 'FEE', initialVisible: false, color: '#93c5fd', formatter: 'currency',},
                {key: 'GROSS', initialVisible: false, color: '#93c5fd', formatter: 'currency',},
            ],
        },
        {
            name: 'Lifetime Paying',
            formatter: 'number',
            data: [
                {key: 'ARPU', initialVisible: false, color: '#93c5fd', formatter: 'currency',},
                {key: 'ARPPU', initialVisible: false, color: '#93c5fd', formatter: 'currency',},
                {key: 'PU', initialVisible: false, color: '#93c5fd', formatter: 'number',},
                // {key: 'TAU', initialVisible: false, color: '#93c5fd',},
            ],
        },
    ],
};

Object.keys(charts).forEach(category => {
    charts[category].forEach(function(chartGroup, chartIndex) {
        let info = ``;

        chartGroup.data.forEach(function(data, seriesIndex) {
            let selectedIndex = page.props.auth.user.settings.selectedSeries?.[category]?.[chartIndex];
            data.initialVisible = (selectedIndex == seriesIndex) || (!selectedIndex && seriesIndex == 0);

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
    <div class="grid gap-8 grid-cols-1 2xl:grid-cols-2">
        <StatsChart v-for="(chart, chartIndex) in charts.per_date" :chart="chart" :chartIndex="chartIndex" type="per_date" />
        <StatsChart v-for="(chart, chartIndex) in charts.lifetime" :chart="chart" :chartIndex="chartIndex" type="lifetime" />
    </div>
</template>
