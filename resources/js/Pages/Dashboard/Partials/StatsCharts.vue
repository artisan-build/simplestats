<script setup>
import StatsChart from "@/Pages/Dashboard/Partials/StatsChart.vue";
import {formatDate} from "@/Helpers/NumberHelpers.js";
import { periodMap, XAU} from "@/Helpers/StringHelpers.js";
import {getKpiDefinitions} from "@/Data/KpiDefinitions.js";
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    filters: Object,
    teamStats: Object,
});

const page = usePage();

const kpiDefs = getKpiDefinitions(props.filters.rangeType);

const extractStatsData4Chart = (statsGroups) => {
    const data = Object.values(props.teamStats.stats).reduce((acc, item, key) => {
        for (let group of statsGroups) {
            if (!(group.key in acc.series)) {
                acc.series[group.key] = {
                    // chart specific
                    data: [],
                    name: group.title ?? group.key,
                    nameSmall: group.titleSmall ?? group.title ?? group.key,
                    color: group.color ?? '',
                    // extras...
                    key: group.key,
                    formatter: group.formatter,
                    indicator: group.indicatorCallback(),
                };
            }

            acc.series[group.key].data.push(item[group.key]);
        }

        acc.categories.push(formatDate(item.date, props.filters.rangeType));

        return acc;
    }, { series: {}, categories: [] });

    data.series = Object.values(data.series).map(item => ({
        ...item,
        data: item.data.reverse()
    }));

    data.categories.reverse();

    return data;
};

let statsArray = Object.values(props.teamStats.stats);
let statsSum = {};
let statsSumLength = {};
statsArray.forEach(item => {
    Object.keys(item).forEach(key => {
        if (key === 'date' || key === 'date_clear') {
            return;
        }

        statsSum[key] = (statsSum[key] ?? 0) + item[key];
        statsSumLength[key] = (statsSumLength[key] ?? 0) + 1;
    });
});

const lastStat = (key) => {
    if (! statsArray.length) {
        return 0;
    }

    let stat = statsArray.at(0);

    if (statsArray.length > 1) {
        let prevStat = statsArray.at(1);
        // console.log(stat, prevStat)
        if (formatDate(stat.date, props.filters.rangeType) === formatDate(page.props.rangeCurrent, props.filters.rangeType) && stat[key] < prevStat[key]) {
            stat = prevStat;
        }
    }

    return stat[key];
}
function safeDevision(numerator, denominator) {
    return denominator == 0 ? 0 : numerator / denominator;
}

let charts = [
    {
        name: 'Conversion',
        formatter: 'number',
        class: 'order-1 3xl:order-1',
        group: [
            {key: 'pd_VISITOR', color: '#93c5fd', formatter: 'number', indicatorCallback: function () {
                return statsSum.pd_VISITOR;
            }},
            {key: 'pd_REG', color: '#93c5fd', formatter: 'number', indicatorCallback: function () {
                return statsSum.pd_REG;
            }},
            {key: 'pd_CR', color: '#93c5fd', formatter: 'percent', indicatorCallback: function () {
                return safeDevision(statsSum.pd_REG, statsSum.pd_VISITOR) * 100;
            }},
        ],
    },
    {
        name: 'Revenue',
        formatter: 'currency',
        class: 'order-2 3xl:order-2',
        group: [
            {key: 'pd_NET', color: '#93c5fd', formatter: 'currency', indicatorCallback: function () {
                return statsSum.pd_NET;
            }},
            {key: 'pd_FEE', color: '#93c5fd', formatter: 'currency', indicatorCallback: function () {
                return statsSum.pd_FEE;
            }},
            {key: 'pd_GROSS', color: '#93c5fd', formatter: 'currency', indicatorCallback: function () {
                return statsSum.pd_GROSS;
            }},
        ],
    },
    {
        name: 'Growth',
        formatter: 'number',
        class: 'order-3 3xl:order-4',
        group: [
            {key: 'pd_NAU', color: '#93c5fd', formatter: 'number',  indicatorCallback: function () {
                return statsSum.pd_NAU;
            }},
            {key: 'pd_NAPU', color: '#93c5fd', formatter: 'number',  indicatorCallback: function () {
                return statsSum.pd_NAPU;
            }},
            {key: 'lt_PU', color: '#93c5fd', formatter: 'number',  indicatorCallback: function () {
                return lastStat('lt_PU');
            }},
            // {key: 'lt_TAU', color: '#93c5fd',  indicatorCallback: function () {
            //     return statsSum.pd_GROSS;
            // }},
        ],
    },
    {
        name: 'Average Revenue',
        formatter: 'currency',
        class: 'order-4 3xl:order-3',
        group: [
            {key: 'lt_ARPU', color: '#93c5fd', formatter: 'currency',  indicatorCallback: function () {
                return lastStat('lt_ARPU');
            }},
            {key: 'lt_ARPPU', color: '#93c5fd', formatter: 'currency',  indicatorCallback: function () {
                return lastStat('lt_ARPPU');
            }},
            {key: 'pd_ARPDAU', color: '#93c5fd', formatter: 'currency',  indicatorCallback: function () {
                return lastStat('pd_ARPDAU');
            }},
        ],
    },
    {
        name: 'Active Users',
        formatter: 'number',
        class: 'order-5 3xl:order-5',
        group: [
            {key: 'pd_DAU', color: '#93c5fd', formatter: 'number',  indicatorCallback: function () {
                return lastStat('pd_DAU');
            }},
            {key: 'pd_DAPU', color: '#93c5fd', formatter: 'number',  indicatorCallback: function () {
                return lastStat('pd_DAPU');
            }},
            {key: 'pd_DAAPU', color: '#93c5fd', formatter: 'number',  indicatorCallback: function () {
                return lastStat('pd_DAAPU');
            }},
        ],
    },
    {
        name: 'Engagement',
        formatter: 'percent',
        class: 'order-6 3xl:order-6',
        group: [
            {key: 'lt_REG2PU', color: '#93c5fd', formatter: 'percent',  indicatorCallback: function () {
                return lastStat('lt_REG2PU');
            }},
            {key: 'lt_REG2DAU', color: '#93c5fd', formatter: 'percent',  indicatorCallback: function () {
                return lastStat('lt_REG2DAU');
            }},
            {key: 'lt_REG2TAU', color: '#93c5fd', formatter: 'percent',  indicatorCallback: function () {
                return lastStat('lt_REG2TAU');
            }},
        ],
    },
];

if (! page.props.auth.user.settings.showAdvanced) {
    charts = charts.filter(function (item) {return item.name !== 'Average Revenue' && item.name !== 'Engagement';});
}

charts.forEach(function(chartGroup) {
    let info = ``;

    chartGroup.group.forEach(function(group) {
        let kpiDef = kpiDefs[group.key];

        let title = (kpiDef.long) ? `${kpiDef.long} (${kpiDef.short})`: `${kpiDef.short}` ;
        info += `<div>
                    <div class="font-bold text-green-300">${title}</div>
                    <div>${kpiDef.description}</div>
                 </div>`;

        group.title = kpiDef.short;

        if (kpiDef.small) {
            group.titleSmall = kpiDef.small;
        }
    });

    chartGroup.info = info;
    chartGroup.data = extractStatsData4Chart(chartGroup.group);

    if (chartGroup.data.categories.length === 1) {
        chartGroup.data.categories[1] = chartGroup.data.categories[0];

        chartGroup.data.series.forEach(function(serie, index) {
            let value = serie.data[0];
            serie.data[0] = 0;
            serie.data[1] = value;
        });
    }
});
</script>

<template>
    <div class="grid gap-8 xl:grid-cols-2" :class="(page.props.auth.user.settings.showAdvanced) ? '3xl:grid-cols-3' : '5xl:grid-cols-4'">
        <StatsChart v-for="(chart, chartIndex) in charts" :chart="chart" :chartIndex="chartIndex" />
    </div>
</template>
