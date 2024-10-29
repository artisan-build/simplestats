<script setup>
import StatsChart from "@/Pages/Dashboard/Partials/StatsChart.vue";
import {formatDate} from "@/Helpers/NumberHelpers.js";
import { periodMap, XAU} from "@/Helpers/StringHelpers.js";
import {getKpiDefinitions} from "@/Data/KpiDefinitions.js";
import {usePage} from "@inertiajs/vue3";
import {forEach} from "lodash";

const props = defineProps({
    filters: Object,
    teamStats: Object,
    currentProject: Object,
    trackingUsageBits: Object,
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

        if (formatDate(stat.date, props.filters.rangeType) === formatDate(page.props.rangeCurrent, props.filters.rangeType) && stat[key] < prevStat[key]) {
            stat = prevStat;
        }
    }

    return stat[key];
}
function safeDevision(numerator, denominator) {
    return denominator == 0 ? 0 : numerator / denominator;
}

function currentProjectHasTrackingUsageBits(bits) {
    return bits.every(bit => (props.trackingUsageBits[bit] & props.currentProject.usage));
}

const charts = [];

// !! CONVERSION
const conversionChart = {
    name: 'Conversion',
    formatter: 'number',
    class: 'order-1 3xl:order-1',
    group: [],
};

if (currentProjectHasTrackingUsageBits(['visitors'])) {
    conversionChart.group.push({key: 'pd_VISITOR', color: '#93c5fd', formatter: 'number', indicatorCallback: () => {return statsSum.pd_VISITOR}});
}

if (currentProjectHasTrackingUsageBits(['users'])) {
    conversionChart.group.push({key: 'pd_REG', color: '#93c5fd', formatter: 'number', indicatorCallback: () => {return statsSum.pd_REG}});

    if (currentProjectHasTrackingUsageBits(['visitors'])) {
        conversionChart.group.push({key: 'pd_CR', color: '#93c5fd', formatter: 'percent', indicatorCallback: () => {return safeDevision(statsSum.pd_REG, statsSum.pd_VISITOR) * 100}});
    }
}

if (conversionChart.group.length > 0) charts.push(conversionChart);

// !! REVENUE
if (currentProjectHasTrackingUsageBits(['payments'])) {
    const revenueChart = {
        name: 'Revenue',
        formatter: 'currency',
        class: 'order-2 3xl:order-2',
        group: [],
    };

    revenueChart.group.push({key: 'pd_NET', color: '#93c5fd', formatter: 'currency', indicatorCallback: () => {return statsSum.pd_NET}});
    revenueChart.group.push({key: 'pd_GROSS', color: '#93c5fd', formatter: 'currency', indicatorCallback: () => {return statsSum.pd_GROSS}});

    if (! currentProjectHasTrackingUsageBits(['visitors'])) {
        revenueChart.group.push({key: 'pd_FEE', color: '#93c5fd', formatter: 'currency', indicatorCallback: () => {return statsSum.pd_FEE}});
    } else {
        revenueChart.group.push({key: 'lt_ARPV', color: '#93c5fd', formatter: 'currency', indicatorCallback: () => {return lastStat('lt_ARPV')}});
    }

    if (revenueChart.group.length > 0) charts.push(revenueChart);
}

// !! ACTIVE USERS
if (currentProjectHasTrackingUsageBits(['users'])) {
    const activeUsersChart = {
        name: 'Active Users',
        formatter: 'number',
        class: 'order-3 3xl:order-5',
        group: [],
    };

    activeUsersChart.group.push({key: 'pd_DAU', color: '#93c5fd', formatter: 'number', indicatorCallback: function () {return lastStat('pd_DAU')}});
    activeUsersChart.group.push({key: 'pd_DAPU', color: '#93c5fd', formatter: 'number', indicatorCallback: function () {return lastStat('pd_DAPU')}});
    activeUsersChart.group.push({key: 'pd_DAAPU', color: '#93c5fd', formatter: 'number', indicatorCallback: function () {return lastStat('pd_DAAPU')}});

    if (activeUsersChart.group.length > 0) charts.push(activeUsersChart);
}

// !! AVERAGE REVENUE
if (currentProjectHasTrackingUsageBits(['payments', 'users'])) {
    const avgRevChart = {
        name: 'Average Revenue',
        formatter: 'currency',
        class: 'order-4 3xl:order-3',
        group: [],
    };

    avgRevChart.group.push({key: 'lt_ARPU', color: '#93c5fd', formatter: 'currency',  indicatorCallback: () => {return lastStat('lt_ARPU')}});
    avgRevChart.group.push({key: 'lt_ARPPU', color: '#93c5fd', formatter: 'currency',  indicatorCallback: () => {return lastStat('lt_ARPPU')}});
    avgRevChart.group.push({key: 'pd_ARPDAU', color: '#93c5fd', formatter: 'currency',  indicatorCallback: () => {return lastStat('pd_ARPDAU')}});

    if (avgRevChart.group.length > 0) charts.push(avgRevChart);
}

// !! GROWTH
if ((charts.length <= 1 || page.props.auth.user.settings.showAdvanced) && currentProjectHasTrackingUsageBits(['users', 'payments'])) {
    const growthChart = {
        name: 'Growth',
        formatter: 'number',
        class: 'order-5 3xl:order-5',
        group: [],
    };

    growthChart.group.push({key: 'pd_NAU', color: '#93c5fd', formatter: 'number', indicatorCallback: () => {return statsSum.pd_NAU}});
    growthChart.group.push({key: 'pd_NAPU', color: '#93c5fd', formatter: 'number', indicatorCallback: () => {return statsSum.pd_NAPU}});
    growthChart.group.push({key: 'lt_PU', color: '#93c5fd', formatter: 'number', indicatorCallback: () => {return lastStat('lt_PU')}});

    if (growthChart.group.length > 0) charts.push(growthChart);
}

// !! ENGAGEMENT
if ((charts.length <= 1 || page.props.auth.user.settings.showAdvanced) && currentProjectHasTrackingUsageBits(['users'])) {
    const engagementChart = {
        name: 'Engagement',
        formatter: 'percent',
        class: 'order-6 3xl:order-6',
        group: [],
    };

    if (currentProjectHasTrackingUsageBits(['payments'])) {
        engagementChart.group.push({key: 'lt_REG2PU', color: '#93c5fd', formatter: 'percent',  indicatorCallback: function () {return lastStat('lt_REG2PU')}});
    }

    engagementChart.group.push({key: 'lt_REG2DAU', color: '#93c5fd', formatter: 'percent',  indicatorCallback: function () {return lastStat('lt_REG2DAU')}});
    engagementChart.group.push({key: 'lt_REG2TAU', color: '#93c5fd', formatter: 'percent',  indicatorCallback: function () {return lastStat('lt_REG2TAU')}});

    if (engagementChart.group.length > 0) charts.push(engagementChart);
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

const chartLenght = charts.length;
</script>

<template>
    <div class="grid gap-8" :class="{
        'xl:grid-cols-1': chartLenght === 1,
        'xl:grid-cols-2': chartLenght >= 2,
        '3xl:grid-cols-3': page.props.auth.user.settings.showAdvanced && chartLenght > 2,
        '5xl:grid-cols-4': !page.props.auth.user.settings.showAdvanced && chartLenght > 2
    }">
        <StatsChart v-for="(chart, chartIndex) in charts" :chart="chart" :chartLength="chartLenght" :chartIndex="chartIndex" />
    </div>
</template>
