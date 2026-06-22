<script setup>
import StatsChart from "@/Pages/Dashboard/Partials/StatsChart.vue";
import {formatDate} from "@/Helpers/NumberHelpers.js";
import {getKpiDefinitions} from "@/Data/KpiDefinitions.js";
import {usePage} from "@inertiajs/vue3";
import {ref} from "vue";
import {useTrackingUsage} from "@/Composables/useTrackingUsage.js";

const props = defineProps({
    filters: Object,
    teamStats: Object,
    currentProject: Object,
    trackingUsageBits: Object,
    mrrSeries: {type: Array, default: null},
});

const page = usePage();

const kpiDefs = getKpiDefinitions(props.filters.range_type);

const isDarkMode = ref(document.documentElement.classList.contains('dark'));

const extractStatsData4Chart = (statsGroups) => {
    const data = Object.values(props.teamStats.stats).reduce((acc, item, key) => {
        for (let group of statsGroups) {
            if (!(group.key in acc.series)) {
                acc.series[group.key] = {
                    // chart specific
                    data: [],
                    dataPrev: [],
                    dates: [],
                    datesPrev: [],
                    name: group.title ?? group.key,
                    nameSmall: group.titleSmall ?? group.title ?? group.key,
                    color: isDarkMode.value ? '#00A3E0' : '#93c5fd',
                    // extras...
                    key: group.key,
                    formatter: group.formatter,
                    indicator: group.indicatorCallback(),
                };

                if (props.teamStats.statsPrev) {
                    acc.series[group.key].namePrev = 'Previous ' + acc.series[group.key].name;
                    acc.series[group.key].nameSmallPrev = 'Previous ' + acc.series[group.key].nameSmall;
                    acc.series[group.key].colorPrev = '#a3b8d3';
                }
            }

            acc.series[group.key].data.push(item[group.key]);
            acc.series[group.key].dates.push(item.date_clear);

            if (props.teamStats.statsPrev) {
                acc.series[group.key].dataPrev.push(props.teamStats.statsPrev[key][group.key]);
                acc.series[group.key].datesPrev.push(props.teamStats.statsPrev[key].date);
            }
        }

        acc.categories.push(formatDate(item.date, props.filters.range_type));

        return acc;
    }, { series: {}, categories: [] });

    data.series = Object.values(data.series).map(item => ({
        ...item,
        data: item.data.reverse(),
        dates: item.dates.reverse(),
        dataPrev: item.dataPrev.reverse(),
        datesPrev: item.datesPrev.reverse()
    }));

    data.categories.reverse();

    return data;
};

// MRR is a point-in-time snapshot per bucket (its own series, not part of the
// period-summed teamStats.stats), so it gets a dedicated chart block. The
// indicator shows the latest snapshot (newest bucket), not a sum, and there is
// no previous-period comparison series. A single-bucket range draws a flat line
// (the value duplicated) instead of the 0 -> value ramp the period charts use,
// because MRR is a stock and never starts at 0.
const buildMrrChart = (mrrSeries) => {
    const ordered = [...mrrSeries].reverse();

    const serie = {
        data: ordered.map(item => item.mrr),
        dataPrev: [],
        dates: ordered.map(item => item.date_clear),
        datesPrev: [],
        name: kpiDefs.mrr.short,
        nameSmall: kpiDefs.mrr.short,
        color: isDarkMode.value ? '#00A3E0' : '#93c5fd',
        key: 'mrr',
        formatter: 'currency',
        indicator: mrrSeries[0].mrr,
    };

    const categories = ordered.map(item => formatDate(item.date, props.filters.range_type));

    if (categories.length === 1) {
        categories[1] = categories[0];
        serie.data[1] = serie.data[0];
        serie.dates[1] = serie.dates[0];
    }

    const kpiDef = kpiDefs.mrr;
    const info = `<div>
                    <div class="font-bold text-green-300">${kpiDef.long} (${kpiDef.short})</div>
                    <div>${kpiDef.description}</div>
                 </div>`;

    return {
        name: 'Recurring Revenue',
        class: 'order-2 3xl:order-2',
        group: [{key: 'mrr', formatter: 'currency', title: kpiDef.short}],
        info,
        data: {series: [serie], categories},
    };
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

        if (formatDate(stat.date, props.filters.range_type) === formatDate(page.props.rangeCurrent, props.filters.range_type) && stat[key] < prevStat[key]) {
            stat = prevStat;
        }
    }

    return stat[key];
}
function safeDevision(numerator, denominator) {
    return denominator == 0 ? 0 : numerator / denominator;
}

const {currentProjectHasTrackingUsageBits} = useTrackingUsage(props.currentProject, props.trackingUsageBits);

const charts = [];

// !! CONVERSION
const conversionChart = {
    name: 'Conversion',
    class: 'order-1 3xl:order-1',
    group: [],
};

if (currentProjectHasTrackingUsageBits(['visitors'])) {
    conversionChart.group.push({key: 'pd_visitor', formatter: 'number', indicatorCallback: () => {return statsSum.pd_visitor}});
}

if (currentProjectHasTrackingUsageBits(['users'])) {
    conversionChart.group.push({key: 'pd_reg', formatter: 'number', indicatorCallback: () => {return statsSum.pd_reg}});

    if (currentProjectHasTrackingUsageBits(['visitors'])) {
        conversionChart.group.push({key: 'pd_cr', formatter: 'percent', indicatorCallback: () => {return safeDevision(statsSum.pd_reg, statsSum.pd_visitor) * 100}});
    }
}

if (conversionChart.group.length > 0) charts.push(conversionChart);

// !! REVENUE
if (currentProjectHasTrackingUsageBits(['payments'])) {
    const revenueChart = {
        name: 'Revenue',
        class: 'order-2 3xl:order-2',
        group: [],
    };

    revenueChart.group.push({key: 'pd_net', formatter: 'currency', indicatorCallback: () => {return statsSum.pd_net}});
    revenueChart.group.push({key: 'pd_gross', formatter: 'currency', indicatorCallback: () => {return statsSum.pd_gross}});

    if (! currentProjectHasTrackingUsageBits(['visitors'])) {
        revenueChart.group.push({key: 'pd_fee', formatter: 'currency', indicatorCallback: () => {return statsSum.pd_fee}});
    } else {
        revenueChart.group.push({key: 'lt_arpv', formatter: 'currency', indicatorCallback: () => {return lastStat('lt_arpv')}});
    }

    if (revenueChart.group.length > 0) charts.push(revenueChart);
}

// !! ACTIVE USERS
if (currentProjectHasTrackingUsageBits(['users'])) {
    const activeUsersChart = {
        name: 'Active Users',
        class: 'order-3 3xl:order-5',
        group: [],
    };

    activeUsersChart.group.push({key: 'pd_dau', formatter: 'number', indicatorCallback: function () {return lastStat('pd_dau')}});

    if (currentProjectHasTrackingUsageBits(['payments'])) {
        activeUsersChart.group.push({key: 'pd_dapu', formatter: 'number', indicatorCallback: function () {return lastStat('pd_dapu')}});
        activeUsersChart.group.push({key: 'pd_daapu', formatter: 'number', indicatorCallback: function () {return lastStat('pd_daapu')}});
    } else {
        activeUsersChart.group.push({key: 'lt_reg2dau', formatter: 'percent',  indicatorCallback: function () {return lastStat('lt_reg2dau')}});
        activeUsersChart.group.push({key: 'pd_nau', formatter: 'number', indicatorCallback: () => {return statsSum.pd_nau}});
    }

    if (activeUsersChart.group.length > 0) charts.push(activeUsersChart);
}

// !! AVERAGE REVENUE
if (currentProjectHasTrackingUsageBits(['payments', 'users'])) {
    const avgRevChart = {
        name: 'Average Revenue',
        class: 'order-4 3xl:order-3',
        group: [],
    };

    avgRevChart.group.push({key: 'lt_arpu', formatter: 'currency',  indicatorCallback: () => {return lastStat('lt_arpu')}});
    avgRevChart.group.push({key: 'lt_arppu', formatter: 'currency',  indicatorCallback: () => {return lastStat('lt_arppu')}});
    avgRevChart.group.push({key: 'pd_arpdau', formatter: 'currency',  indicatorCallback: () => {return lastStat('pd_arpdau')}});

    if (avgRevChart.group.length > 0) charts.push(avgRevChart);
}

// !! GROWTH
if ((charts.length <= 1 || page.props.auth.user.settings.showAdvanced) && currentProjectHasTrackingUsageBits(['users', 'payments'])) {
    const growthChart = {
        name: 'Growth',
        class: 'order-5 3xl:order-5',
        group: [],
    };

    growthChart.group.push({key: 'pd_nau', formatter: 'number', indicatorCallback: () => {return statsSum.pd_nau}});
    growthChart.group.push({key: 'pd_napu', formatter: 'number', indicatorCallback: () => {return statsSum.pd_napu}});
    growthChart.group.push({key: 'lt_pu', formatter: 'number', indicatorCallback: () => {return lastStat('lt_pu')}});

    if (growthChart.group.length > 0) charts.push(growthChart);
}

// !! ENGAGEMENT
if ((charts.length <= 1 || page.props.auth.user.settings.showAdvanced) && currentProjectHasTrackingUsageBits(['users'])) {
    const engagementChart = {
        name: 'Engagement',
        class: 'order-6 3xl:order-6',
        group: [],
    };

    if (currentProjectHasTrackingUsageBits(['payments'])) {
        engagementChart.group.push({key: 'lt_reg2pu', formatter: 'percent',  indicatorCallback: function () {return lastStat('lt_reg2pu')}});
    }

    engagementChart.group.push({key: 'lt_reg2dau', formatter: 'percent',  indicatorCallback: function () {return lastStat('lt_reg2dau')}});
    engagementChart.group.push({key: 'lt_reg2tau', formatter: 'percent',  indicatorCallback: function () {return lastStat('lt_reg2tau')}});

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

// Pushed after the loop above so its pre-built snapshot data is not overwritten
// by extractStatsData4Chart (which reads the period-summed teamStats.stats).
if (currentProjectHasTrackingUsageBits(['payments']) && props.mrrSeries?.length) {
    charts.push(buildMrrChart(props.mrrSeries));
}

const chartLenght = charts.length;
</script>

<template>
    <div class="grid gap-4" :class="{
        'md:grid-cols-1': chartLenght === 1,
        'md:grid-cols-2': chartLenght >= 2,
        '3xl:grid-cols-3': page.props.auth.user.settings.showAdvanced && chartLenght > 2,
        '5xl:grid-cols-4': !page.props.auth.user.settings.showAdvanced && chartLenght > 2
    }">
        <StatsChart v-for="(chart, chartIndex) in charts"
            :chart="chart"
            :chartLength="chartLenght"
            :chartIndex="chartIndex"
            :filters="filters"
        />
    </div>
</template>
