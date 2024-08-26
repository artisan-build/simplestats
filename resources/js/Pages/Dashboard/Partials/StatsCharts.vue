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
                    initialVisible: group.initialVisible,
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

let statsSumWithoutCurrent = {};
let statsSumWithoutCurrentLength = {};
statsArray.forEach(item => {
    Object.keys(item).forEach(key => {
        if (Object.values(props.teamStats.stats).length > 1 && formatDate(item['date'], props.filters.rangeType) === formatDate(page.props.rangeCurrent, props.filters.rangeType)) {
            return;
        }

        if (key === 'date' || key === 'date_clear') {
            return;
        }

        statsSumWithoutCurrent[key] = (statsSumWithoutCurrent[key] ?? 0) + item[key];
        statsSumWithoutCurrentLength[key] = (statsSumWithoutCurrentLength[key] ?? 0) + 1;
    });
});

function safeDevision(numerator, denominator) {
    return denominator == 0 ? 0 : numerator / denominator;
}

let charts = [
    {
        name: 'Conversion',
        formatter: 'number',
        class: 'order-1 3xl:order-1',
        group: [
            {key: 'pd_VISITOR', initialVisible: false, color: '#93c5fd', formatter: 'number', indicatorCallback: function () {
                return statsSum.pd_VISITOR;
            }},
            {key: 'pd_REG', initialVisible: false, color: '#93c5fd', formatter: 'number', indicatorCallback: function () {
                return statsSum.pd_REG;
            }},
            {key: 'pd_CR', initialVisible: false, color: '#93c5fd', formatter: 'percent', indicatorCallback: function () {
                return safeDevision(statsSum.pd_REG, statsSum.pd_VISITOR) * 100;
            }},
        ],
    },
    {
        name: 'Revenue',
        formatter: 'currency',
        class: 'order-2 3xl:order-2',
        group: [
            {key: 'pd_NET', initialVisible: false, color: '#93c5fd', formatter: 'currency', indicatorCallback: function () {
                return statsSum.pd_NET;
            }},
            {key: 'pd_FEE', initialVisible: false, color: '#93c5fd', formatter: 'currency', indicatorCallback: function () {
                return statsSum.pd_FEE;
            }},
            {key: 'pd_GROSS', initialVisible: false, color: '#93c5fd', formatter: 'currency', indicatorCallback: function () {
                return statsSum.pd_GROSS;
            }},
        ],
    },
    {
        name: 'Growth',
        formatter: 'number',
        class: 'order-3 3xl:order-4',
        group: [
            {key: 'pd_NAU', initialVisible: false, color: '#93c5fd', formatter: 'number',  indicatorCallback: function () {
                return statsSum.pd_NAU;
            }},
            {key: 'pd_NAPU', initialVisible: false, color: '#93c5fd', formatter: 'number',  indicatorCallback: function () {
                return statsSum.pd_NAPU;
            }},
            {key: 'pd_PU', initialVisible: false, color: '#93c5fd', formatter: 'number',  indicatorCallback: function () {
                return statsSum.pd_PU;
            }},
            // {key: 'lt_TAU', initialVisible: false, color: '#93c5fd',  indicatorCallback: function () {
            //     return statsSum.pd_GROSS;
            // }},
        ],
    },
    {
        name: 'Average Revenue',
        formatter: 'currency',
        class: 'order-4 3xl:order-3',
        group: [
            {key: 'lt_ARPU', initialVisible: false, color: '#93c5fd', formatter: 'currency',  indicatorCallback: function () {
                return statsSumWithoutCurrent.lt_ARPU / statsSumWithoutCurrentLength.lt_ARPU;
            }},
            {key: 'lt_ARPPU', initialVisible: false, color: '#93c5fd', formatter: 'currency',  indicatorCallback: function () {
                return statsSumWithoutCurrent.lt_ARPPU / statsSumWithoutCurrentLength.lt_ARPPU;
            }},
            {key: 'pd_ARPDAU', initialVisible: false, color: '#93c5fd', formatter: 'currency',  indicatorCallback: function () {
                return statsSumWithoutCurrent.pd_ARPDAU / statsSumWithoutCurrentLength.pd_ARPDAU;
            }},
        ],
    },
    {
        name: 'Active Users',
        formatter: 'number',
        class: 'order-5 3xl:order-5',
        group: [
            {key: 'pd_DAU', initialVisible: false, color: '#93c5fd', formatter: 'number',  indicatorCallback: function () {
                return statsSumWithoutCurrent.pd_DAU / statsSumWithoutCurrentLength.pd_DAU;
            }},
            {key: 'pd_DAPU', initialVisible: false, color: '#93c5fd', formatter: 'number',  indicatorCallback: function () {
                return statsSumWithoutCurrent.pd_DAPU / statsSumWithoutCurrentLength.pd_DAPU;
            }},
            {key: 'pd_DAAPU', initialVisible: false, color: '#93c5fd', formatter: 'number',  indicatorCallback: function () {
                return statsSumWithoutCurrent.pd_DAAPU / statsSumWithoutCurrentLength.pd_DAAPU;
            }},
        ],
    },
    {
        name: 'Engagement',
        formatter: 'percent',
        class: 'order-6 3xl:order-6',
        group: [
            {key: 'lt_REG2PU', initialVisible: false, color: '#93c5fd', formatter: 'percent',  indicatorCallback: function () {
                return statsSumWithoutCurrent.lt_REG2PU / statsSumWithoutCurrentLength.lt_REG2PU;
            }},
            {key: 'lt_REG2DAU', initialVisible: false, color: '#93c5fd', formatter: 'percent',  indicatorCallback: function () {
                return statsSumWithoutCurrent.lt_REG2DAU / statsSumWithoutCurrentLength.lt_REG2DAU;
            }},
            {key: 'lt_REG2TAU', initialVisible: false, color: '#93c5fd', formatter: 'percent',  indicatorCallback: function () {
                return statsSumWithoutCurrent.lt_REG2TAU / statsSumWithoutCurrentLength.lt_REG2TAU;
            }},
        ],
    },
];

if (! page.props.auth.user.settings.showAdvanced) {
    charts = charts.filter(function (item) {return item.name !== 'Average Revenue' && item.name !== 'Engagement';});
}

charts.forEach(function(chartGroup, chartIndex) {
    let info = ``;

    chartGroup.group.forEach(function(group, groupIndex) {
        let selectedIndex = page.props.auth.user.settings?.selectedSeries?.[chartIndex];
        group.initialVisible = (selectedIndex == groupIndex) || (!selectedIndex && groupIndex == 0);

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
