<script setup>
import StatsChart from "@/Pages/Dashboard/Partials/StatsChart.vue";
import CampaignKpiBar from "@/Pages/Campaigns/Partials/CampaignKpiBar.vue";
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
    mrrSeriesPrev: {type: Array, default: null},
    campaignSummary: {type: Object, default: null},
});

const page = usePage();

const kpiDefs = getKpiDefinitions(props.filters.range_type);

const isDarkMode = ref(document.documentElement.classList.contains('dark'));

const PRESET_RANK = {simple: 0, advanced: 1, expert: 2};
const dashboardPreset = page.props.auth.user.settings.dashboardPreset ?? 'advanced';
const presetRank = PRESET_RANK[dashboardPreset] ?? 0;
const isExpertLayout = dashboardPreset === 'expert';

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

// MRR/Subscribers are point-in-time snapshots per bucket (their own series, not
// part of the period-summed teamStats.stats), so they get a dedicated chart block
// whose indicator is the latest snapshot, not a sum. When a comparison is active they
// get a previous-period line from mrrSeriesPrev (the prior range's snapshots); Net
// carries its comparison from teamStats.statsPrev. A single-bucket range draws a flat
// line (value duplicated) instead of the 0 -> value ramp the period charts use,
// because MRR is a stock and never starts at 0.
const buildMrrChart = (mrrSeries) => {
    const ordered = [...mrrSeries].reverse();
    const orderedPrev = props.mrrSeriesPrev?.length ? [...props.mrrSeriesPrev].reverse() : null;
    const latest = mrrSeries[0];
    const color = isDarkMode.value ? '#00A3E0' : '#93c5fd';
    const dates = ordered.map(item => item.date_clear);

    const snapshotPrev = (key) => orderedPrev ? ordered.map((item, i) => orderedPrev[i]?.[key] ?? 0) : [];
    const snapshotDatesPrev = () => orderedPrev ? ordered.map((item, i) => orderedPrev[i]?.date) : [];

    const prevList = props.teamStats.statsPrev ? Object.values(props.teamStats.statsPrev) : null;
    const netByDate = {};
    const netPrevByDate = {};
    const prevDateByDate = {};
    Object.values(props.teamStats.stats).forEach((item, i) => {
        netByDate[item.date_clear] = item.pd_net;
        if (prevList) {
            netPrevByDate[item.date_clear] = prevList[i]?.pd_net ?? 0;
            prevDateByDate[item.date_clear] = prevList[i]?.date;
        }
    });

    const seriesDefs = [
        {
            key: 'mrr', tier: 'simple', formatter: 'currency',
            data: ordered.map(item => item.mrr),
            dataPrev: snapshotPrev('mrr'),
            datesPrev: snapshotDatesPrev(),
            indicator: latest.mrr,
        },
        {
            key: 'subscribers', tier: 'simple', formatter: 'number',
            data: ordered.map(item => item.subscribers),
            dataPrev: snapshotPrev('subscribers'),
            datesPrev: snapshotDatesPrev(),
            indicator: latest.subscribers,
        },
        // Net is a period flow (from teamStats.stats) aligned onto the MRR snapshot buckets so the headline revenue sits with MRR.
        {
            key: 'pd_net', tier: 'simple', formatter: 'currency',
            data: ordered.map(item => netByDate[item.date_clear] ?? 0),
            dataPrev: prevList ? ordered.map(item => netPrevByDate[item.date_clear] ?? 0) : [],
            datesPrev: prevList ? ordered.map(item => prevDateByDate[item.date_clear]) : [],
            indicator: statsSum.pd_net,
        },
    ].filter(def => PRESET_RANK[def.tier] <= presetRank);

    const series = seriesDefs.map(def => {
        const hasPrev = !! def.dataPrev?.length;
        const name = kpiDefs[def.key].short;
        const namePrev = hasPrev ? 'Previous ' + name : undefined;

        return {
            data: [...def.data],
            dataPrev: hasPrev ? [...def.dataPrev] : [],
            dates: [...dates],
            datesPrev: hasPrev ? [...def.datesPrev] : [],
            name,
            nameSmall: name,
            namePrev,
            nameSmallPrev: namePrev,
            color,
            colorPrev: hasPrev ? '#a3b8d3' : undefined,
            key: def.key,
            formatter: def.formatter,
            indicator: def.indicator,
        };
    });

    const categories = ordered.map(item => formatDate(item.date, props.filters.range_type));

    if (categories.length === 1) {
        categories[1] = categories[0];
        series.forEach(serie => {
            serie.data[1] = serie.data[0];
            serie.dates[1] = serie.dates[0];

            if (serie.dataPrev.length) {
                serie.dataPrev[1] = serie.dataPrev[0];
                serie.datesPrev[1] = serie.datesPrev[0];
            }
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
        name: 'Revenue',
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

// ARPS is derived from the MRR snapshot series; inject it per bucket (current and
// comparison) so it can live in the period-based Average Revenue chart alongside ARPU/ARPV.
const injectArps = (series, buckets) => {
    if (! series?.length || ! buckets?.length) {
        return;
    }

    const arpsByDate = {};
    series.forEach(item => { arpsByDate[item.date_clear] = item.subscribers > 0 ? item.mrr / item.subscribers : 0; });
    buckets.forEach(item => { item.arps = arpsByDate[item.date_clear] ?? 0; });
};

injectArps(props.mrrSeries, statsArray);
injectArps(props.mrrSeriesPrev, props.teamStats.statsPrev ? Object.values(props.teamStats.statsPrev) : null);

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

const showsRegistrations = currentProjectHasTrackingUsageBits(['users']);

const recurringShown = currentProjectHasTrackingUsageBits(['payments', 'users']) && !! props.mrrSeries?.length;

// A KPI shows when its tier is within the active preset AND the project tracks
// the required bits. exceptBits excludes a KPI when an additional bit is present
// (e.g. NAU only as the Activity fallback for projects without payments).
// hideWhenRecurring drops the fallback Net in the average-revenue chart when the
// recurring revenue chart already carries Net.
const kpiVisible = (kpi) =>
    PRESET_RANK[kpi.tier] <= presetRank
    && (! kpi.bits || currentProjectHasTrackingUsageBits(kpi.bits))
    && (! kpi.exceptBits || ! currentProjectHasTrackingUsageBits(kpi.exceptBits))
    && (! kpi.requiresRecurring || recurringShown)
    && ! (kpi.hideWhenRecurring && recurringShown);

const chartDefs = [
    {
        name: 'Acquisition',
        order: 1,
        group: [
            {key: 'pd_visitor', formatter: 'number', tier: 'simple', bits: ['visitors'], indicatorCallback: () => statsSum.pd_visitor},
            {key: 'pd_reg', formatter: 'number', tier: 'simple', bits: ['users'], indicatorCallback: () => statsSum.pd_reg},
            {key: 'pd_cr', formatter: 'percent', tier: 'simple', bits: ['users', 'visitors'], indicatorCallback: () => safeDevision(statsSum.pd_reg, statsSum.pd_visitor) * 100},
        ],
    },
    {
        // When recurring revenue exists, Net lives in the Revenue chart and this one is the pure ARP* family; otherwise it carries Net and is the project's revenue chart.
        name: recurringShown ? 'Average Revenue' : 'Revenue',
        order: 4,
        group: [
            {key: 'pd_net', formatter: 'currency', tier: 'simple', bits: ['payments'], hideWhenRecurring: true, tooltipExtra: [{key: 'pd_gross', label: 'Gross'}, {key: 'pd_fee', label: 'Fee'}], indicatorCallback: () => statsSum.pd_net},
            {key: 'lt_arpu', formatter: 'currency', tier: 'advanced', bits: ['payments', 'users'], indicatorCallback: () => lastStat('lt_arpu')},
            // Revenue per visitor is tiny, so 2 decimals lose all granularity; show 3.
            // Simple-tier for payment projects without users (they have no MRR chart to anchor revenue).
            {key: 'lt_arpv', formatter: 'currency', decimals: 3, tier: 'simple', bits: ['payments', 'visitors'], exceptBits: ['users'], indicatorCallback: () => lastStat('lt_arpv')},
            {key: 'lt_arpv', formatter: 'currency', decimals: 3, tier: 'advanced', bits: ['payments', 'visitors', 'users'], indicatorCallback: () => lastStat('lt_arpv')},
            {key: 'arps', formatter: 'currency', tier: 'advanced', bits: ['payments', 'users'], requiresRecurring: true, indicatorCallback: () => lastStat('arps')},
        ],
    },
    {
        name: 'Activity',
        order: 3,
        group: [
            // Projects without payments have no revenue chart, so Activity becomes their simple-tier second chart.
            {key: 'pd_dau', formatter: 'number', tier: 'simple', bits: ['users'], exceptBits: ['payments'], indicatorCallback: () => lastStat('pd_dau')},
            {key: 'pd_nau', formatter: 'number', tier: 'simple', bits: ['users'], exceptBits: ['payments'], indicatorCallback: () => statsSum.pd_nau},
            {key: 'lt_reg2dau', formatter: 'percent', tier: 'simple', bits: ['users'], exceptBits: ['payments'], indicatorCallback: () => lastStat('lt_reg2dau')},
            {key: 'pd_dau', formatter: 'number', tier: 'advanced', bits: ['users', 'payments'], indicatorCallback: () => lastStat('pd_dau')},
            {key: 'pd_dapu', formatter: 'number', tier: 'advanced', bits: ['users', 'payments'], indicatorCallback: () => lastStat('pd_dapu')},
            {key: 'lt_pu', formatter: 'number', tier: 'advanced', bits: ['users', 'payments'], indicatorCallback: () => lastStat('lt_pu')},
        ],
    },
    {
        name: 'Growth',
        order: 5,
        group: [
            {key: 'pd_nau', formatter: 'number', tier: 'expert', bits: ['users', 'payments'], indicatorCallback: () => statsSum.pd_nau},
            {key: 'pd_napu', formatter: 'number', tier: 'expert', bits: ['users', 'payments'], indicatorCallback: () => statsSum.pd_napu},
            {key: 'pd_arpdau', formatter: 'currency', tier: 'expert', bits: ['users', 'payments'], indicatorCallback: () => lastStat('pd_arpdau')},
        ],
    },
    {
        name: 'Funnel & Depth',
        order: 6,
        group: [
            {key: 'lt_reg2dau', formatter: 'percent', tier: 'expert', bits: ['users', 'payments'], indicatorCallback: () => lastStat('lt_reg2dau')},
            {key: 'lt_reg2pu', formatter: 'percent', tier: 'expert', bits: ['users', 'payments'], indicatorCallback: () => lastStat('lt_reg2pu')},
            {key: 'lt_arppu', formatter: 'currency', tier: 'expert', bits: ['users', 'payments'], indicatorCallback: () => lastStat('lt_arppu')},
        ],
    },
];

const charts = chartDefs
    .map(def => ({...def, group: def.group.filter(kpiVisible)}))
    .filter(chart => chart.group.length > 0);

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
if (currentProjectHasTrackingUsageBits(['recurring']) && props.mrrSeries?.length) {
    charts.push(buildMrrChart(props.mrrSeries));
}

charts.sort((a, b) => a.order - b.order);

const chartLenght = charts.length;

// The last chart stretches to fill whatever cells stay empty in its final grid
// row, so any count lands flush. This depends only on count and column count, not
// on which charts the tracking bits produced. Computed per breakpoint (md = 2
// columns; the wide breakpoint is 3 in the dense expert layout, 4 otherwise).
if (chartLenght > 1) {
    const fillSpan = (columns) => columns - ((chartLenght - 1) % columns);
    const wide = isExpertLayout ? {bp: '3xl', columns: 3} : {bp: '5xl', columns: 4};
    const wideSpan = chartLenght > 2 ? fillSpan(wide.columns) : 1;

    charts[chartLenght - 1].class = `md:col-span-${fillSpan(2)} ${wide.bp}:col-span-${wideSpan}`;
}
</script>

<template>
    <div class="space-y-4">
        <CampaignKpiBar v-if="campaignSummary" :summary="campaignSummary" :showsRegistrations="showsRegistrations" />
        <p v-else-if="filters.campaign" class="text-sm text-gray-500 dark:text-dark-400">
            Spend-based campaign KPIs (ROAS, CAC, net profit) only apply to the full campaign. Clear the other active filters to see them.
        </p>
        <div class="grid gap-4" :class="{
            'md:grid-cols-1': chartLenght === 1,
            'md:grid-cols-2': chartLenght >= 2,
            '3xl:grid-cols-3': isExpertLayout && chartLenght > 2,
            '5xl:grid-cols-4': !isExpertLayout && chartLenght > 2
        }">
            <div class="hidden md:col-span-1 md:col-span-2 3xl:col-span-1 3xl:col-span-2 3xl:col-span-3 5xl:col-span-1 5xl:col-span-2 5xl:col-span-3 5xl:col-span-4"></div>
            <StatsChart v-for="(chart, chartIndex) in charts"
                :chart="chart"
                :chartLength="chartLenght"
                :chartIndex="chartIndex"
                :filters="filters"
            />
        </div>
    </div>
</template>
