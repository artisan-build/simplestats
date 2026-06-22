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
                    decimals: group.decimals,
                    indicator: group.indicatorCallback(),
                };

                if (props.teamStats.statsPrev) {
                    acc.series[group.key].namePrev = 'Previous ' + acc.series[group.key].name;
                    acc.series[group.key].nameSmallPrev = 'Previous ' + acc.series[group.key].nameSmall;
                    acc.series[group.key].colorPrev = '#a3b8d3';
                }

                if (group.tooltipExtra) {
                    acc.series[group.key].tooltipExtra = group.tooltipExtra.map(t => ({
                        label: t.label,
                        formatter: t.formatter ?? 'currency',
                        data: [],
                    }));
                }
            }

            acc.series[group.key].data.push(item[group.key]);
            acc.series[group.key].dates.push(item.date_clear);

            if (acc.series[group.key].tooltipExtra) {
                group.tooltipExtra.forEach((t, i) => acc.series[group.key].tooltipExtra[i].data.push(item[t.key]));
            }

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
        datesPrev: item.datesPrev.reverse(),
        tooltipExtra: item.tooltipExtra
            ? item.tooltipExtra.map(t => ({...t, data: [...t.data].reverse()}))
            : undefined,
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
    const latest = mrrSeries[0];
    const color = isDarkMode.value ? '#00A3E0' : '#93c5fd';
    const dates = ordered.map(item => item.date_clear);

    const arps = (item) => item.subscribers > 0 ? item.mrr / item.subscribers : 0;

    const seriesDefs = [
        {key: 'mrr', formatter: 'currency', data: ordered.map(item => item.mrr), indicator: latest.mrr},
        {key: 'subscribers', formatter: 'number', data: ordered.map(item => item.subscribers), indicator: latest.subscribers},
        {key: 'arps', formatter: 'currency', data: ordered.map(arps), indicator: arps(latest)},
    ];

    const series = seriesDefs.map(def => ({
        data: [...def.data],
        dataPrev: [],
        dates: [...dates],
        datesPrev: [],
        name: kpiDefs[def.key].short,
        nameSmall: kpiDefs[def.key].short,
        color,
        key: def.key,
        formatter: def.formatter,
        indicator: def.indicator,
    }));

    const categories = ordered.map(item => formatDate(item.date, props.filters.range_type));

    if (categories.length === 1) {
        categories[1] = categories[0];
        series.forEach(serie => {
            serie.data[1] = serie.data[0];
            serie.dates[1] = serie.dates[0];
        });
    }

    const info = seriesDefs.map(def => {
        const kpiDef = kpiDefs[def.key];

        return `<div>
                    <div class="font-bold text-green-300">${kpiDef.long} (${kpiDef.short})</div>
                    <div>${kpiDef.description}</div>
                 </div>`;
    }).join('');

    return {
        name: 'Recurring Revenue',
        order: 2,
        group: seriesDefs.map(def => ({key: def.key, formatter: def.formatter, title: kpiDefs[def.key].short})),
        info,
        data: {series, categories},
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

// !! ACQUISITION
const acquisitionChart = {
    name: 'Acquisition',
    order: 1,
    group: [],
};

if (currentProjectHasTrackingUsageBits(['visitors'])) {
    acquisitionChart.group.push({key: 'pd_visitor', formatter: 'number', indicatorCallback: () => {return statsSum.pd_visitor}});
}

if (currentProjectHasTrackingUsageBits(['users'])) {
    acquisitionChart.group.push({key: 'pd_reg', formatter: 'number', indicatorCallback: () => {return statsSum.pd_reg}});

    if (currentProjectHasTrackingUsageBits(['visitors'])) {
        acquisitionChart.group.push({key: 'pd_cr', formatter: 'percent', indicatorCallback: () => {return safeDevision(statsSum.pd_reg, statsSum.pd_visitor) * 100}});
    }
}

if (acquisitionChart.group.length > 0) charts.push(acquisitionChart);

// !! REVENUE
if (currentProjectHasTrackingUsageBits(['payments'])) {
    const revenueChart = {
        name: 'Revenue',
        order: 4,
        group: [],
    };

    revenueChart.group.push({
        key: 'pd_net',
        formatter: 'currency',
        indicatorCallback: () => {return statsSum.pd_net},
        tooltipExtra: [
            {key: 'pd_gross', label: 'Gross'},
            {key: 'pd_fee', label: 'Fee'},
        ],
    });

    if (currentProjectHasTrackingUsageBits(['users'])) {
        revenueChart.group.push({key: 'lt_arpu', formatter: 'currency', indicatorCallback: () => {return lastStat('lt_arpu')}});
    }

    if (currentProjectHasTrackingUsageBits(['visitors'])) {
        // Revenue per visitor is tiny, so 2 decimals lose all granularity; show 3.
        revenueChart.group.push({key: 'lt_arpv', formatter: 'currency', decimals: 3, indicatorCallback: () => {return lastStat('lt_arpv')}});
    }

    if (revenueChart.group.length > 0) charts.push(revenueChart);
}

// !! ACTIVITY
if (currentProjectHasTrackingUsageBits(['users'])) {
    const activityChart = {
        name: 'Activity',
        order: 3,
        group: [],
    };

    activityChart.group.push({key: 'pd_dau', formatter: 'number', indicatorCallback: function () {return lastStat('pd_dau')}});

    if (currentProjectHasTrackingUsageBits(['payments'])) {
        activityChart.group.push({key: 'pd_dapu', formatter: 'number', indicatorCallback: function () {return lastStat('pd_dapu')}});
        activityChart.group.push({key: 'lt_pu', formatter: 'number', indicatorCallback: function () {return lastStat('lt_pu')}});
    } else {
        activityChart.group.push({key: 'pd_nau', formatter: 'number', indicatorCallback: () => {return statsSum.pd_nau}});
    }

    if (activityChart.group.length > 0) charts.push(activityChart);
}

// !! GROWTH (advanced)
if (page.props.auth.user.settings.showAdvanced && currentProjectHasTrackingUsageBits(['users', 'payments'])) {
    const growthChart = {
        name: 'Growth',
        order: 5,
        group: [],
    };

    growthChart.group.push({key: 'pd_nau', formatter: 'number', indicatorCallback: () => {return statsSum.pd_nau}});
    growthChart.group.push({key: 'pd_napu', formatter: 'number', indicatorCallback: () => {return statsSum.pd_napu}});
    growthChart.group.push({key: 'pd_arpdau', formatter: 'currency', indicatorCallback: () => {return lastStat('pd_arpdau')}});

    if (growthChart.group.length > 0) charts.push(growthChart);
}

// !! FUNNEL & DEPTH (advanced)
if (page.props.auth.user.settings.showAdvanced && currentProjectHasTrackingUsageBits(['users'])) {
    const funnelChart = {
        name: 'Funnel & Depth',
        order: 6,
        group: [],
    };

    funnelChart.group.push({key: 'lt_reg2dau', formatter: 'percent', indicatorCallback: function () {return lastStat('lt_reg2dau')}});

    if (currentProjectHasTrackingUsageBits(['payments'])) {
        funnelChart.group.push({key: 'lt_reg2pu', formatter: 'percent', indicatorCallback: function () {return lastStat('lt_reg2pu')}});
        funnelChart.group.push({key: 'lt_arppu', formatter: 'currency', indicatorCallback: function () {return lastStat('lt_arppu')}});
    }

    if (funnelChart.group.length > 0) charts.push(funnelChart);
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

        chartGroup.data.series.forEach(function(serie) {
            let value = serie.data[0];
            serie.data[0] = 0;
            serie.data[1] = value;

            if (serie.tooltipExtra) {
                serie.tooltipExtra.forEach(t => { t.data[1] = t.data[0]; });
            }
        });
    }
});

// Pushed after the loop above so its pre-built snapshot data is not overwritten
// by extractStatsData4Chart (which reads the period-summed teamStats.stats).
// Recurring revenue is user-bound (MRR/Subscribers need a stats_user_id), so it
// requires users + payments, not just payments (guest checkout = one-time only).
if (currentProjectHasTrackingUsageBits(['payments', 'users']) && props.mrrSeries?.length) {
    charts.push(buildMrrChart(props.mrrSeries));
}

charts.sort((a, b) => a.order - b.order);

const chartLenght = charts.length;

// The last chart stretches to fill whatever cells stay empty in its final grid
// row, so any count lands flush. This depends only on count and column count, not
// on which charts the tracking bits produced. Computed per breakpoint (md = 2
// columns; the wide breakpoint is 3 in advanced mode, 4 otherwise).
if (chartLenght > 1) {
    const fillSpan = (columns) => columns - ((chartLenght - 1) % columns);
    const wide = page.props.auth.user.settings.showAdvanced ? {bp: '3xl', columns: 3} : {bp: '5xl', columns: 4};
    const wideSpan = chartLenght > 2 ? fillSpan(wide.columns) : 1;

    charts[chartLenght - 1].class = `md:col-span-${fillSpan(2)} ${wide.bp}:col-span-${wideSpan}`;
}
</script>

<template>
    <div class="grid gap-4" :class="{
        'md:grid-cols-1': chartLenght === 1,
        'md:grid-cols-2': chartLenght >= 2,
        '3xl:grid-cols-3': page.props.auth.user.settings.showAdvanced && chartLenght > 2,
        '5xl:grid-cols-4': !page.props.auth.user.settings.showAdvanced && chartLenght > 2
    }">
        <div class="hidden md:col-span-1 md:col-span-2 3xl:col-span-1 3xl:col-span-2 3xl:col-span-3 5xl:col-span-1 5xl:col-span-2 5xl:col-span-3 5xl:col-span-4"></div>
        <StatsChart v-for="(chart, chartIndex) in charts"
            :chart="chart"
            :chartLength="chartLenght"
            :chartIndex="chartIndex"
            :filters="filters"
        />
    </div>
</template>
