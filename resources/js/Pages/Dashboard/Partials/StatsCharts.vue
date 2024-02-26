<script setup>
import StatsChart from "@/Pages/Dashboard/Partials/StatsChart.vue";
import {formatCurrency, formatNumber, formatPercent, formatDate} from "@/Helpers/NumberHelpers.js";
import {XAAPU, XAPU, XAU} from "@/Helpers/StringHelpers.js";

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
                        // chart specific
                        data: [],
                        name: field.title ?? field.key,
                        visible: field.visible,
                        color: field.color ?? '',
                        // extras...
                        formatter: field.formatter,
                        activeCss: field.activeCss ?? '',
                        customCss: field.customCss ?? '',
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
            name: 'Revenue by Period',
            // nameShort: 'NAU',
            formatter: formatCurrency,
            data: extractStatsData4Chart('per_date', [
                {key: 'NET', visible: true, title: 'Net Revenue', color: '#93c5fd', formatter: formatCurrency, customCss: 'rounded-bl-md', activeCss: 'border-blue-400',},
                {key: 'GROSS', visible: false, title: 'Gross Revenue', color: '#60a5fa', formatter: formatCurrency, activeCss: 'border-blue-500',},
                // {key: 'FEE', visible: false, title: 'Fees', color: '#ffb3ba', formatter: formatCurrency, activeCss: '',},
                {key: 'ARPDAU', visible: false, title: 'Avg. Revenue Per ' + XAU(props.rangeType), color: '#fce04c', formatter: formatCurrency, activeCss: 'rounded-br-md border-yellow-400',},
            ]),
        },
        {
            name: 'Users by Period',
            formatter: formatNumber,
            data: extractStatsData4Chart('per_date', [
                {key: 'REG', visible: true, title: 'Registrations', color: '#87efac', formatter: formatNumber, activeCss: 'border-green-300',},
                {key: 'NAU', visible: false, title: 'New Active User', color: '#22c55d', formatter: formatNumber, activeCss: 'border-green-500',},
                {key: 'NAPU', visible: false, title: 'New Active Paying User', color: '#17803d', formatter: formatNumber, activeCss: 'border-green-700',},
                {key: 'DAU', visible: false, title: XAU(props.rangeType, true), color: '#fbd44c', formatter: formatNumber, customCss: 'rounded-bl-md', activeCss: 'border-yellow-400',},
                {key: 'DAPU', visible: false, title: XAPU(props.rangeType, true), color: '#fbbf24', formatter: formatNumber, activeCss: 'border-yellow-500',},
                {key: 'DAAPU', visible: false, title: XAAPU(props.rangeType, true), color: '#f59e0c', formatter: formatNumber, activeCss: 'rounded-br-md border-yellow-600',},
            ]),
        },
    ],
    lifetime: [
        {
            name: 'Lifetime Revenue',
            formatter: formatCurrency,
            data: extractStatsData4Chart('lifetime', [
                {key: 'NET', visible: true, title: 'Net Revenue', color: '#93c5fd', formatter: formatCurrency, customCss: 'rounded-bl-md', activeCss: 'border-blue-400',},
                // {key: 'GROSS', visible: false, title: 'Gross Revenue', color: '#60a5fa', formatter: formatCurrency, activeCss: 'border-blue-500',},
                {key: 'ARPU', visible: false, title: 'Avg. Revenue Per User', color: '#facc14', formatter: formatCurrency, activeCss: 'border-yellow-500',},
                {key: 'ARPPU', visible: false, title: 'Avg. Revenue Per Paying User', color: '#ebb305', formatter: formatCurrency, activeCss: 'rounded-br-md border-yellow-600',},
            ]),
        },
        {
            name: 'Lifetime Users',
            formatter: formatNumber,
            data: extractStatsData4Chart('lifetime', [
                {key: 'REG', visible: true, title: 'Registrations', color: '#87efac', formatter: formatNumber, activeCss: 'border-green-300',},
                {key: 'PU', visible: false, title: 'Paying User', color: '#a6f3fc', formatter: formatNumber, activeCss: 'border-sky-300',},
                {key: 'TAU', visible: false, title: 'Total Active User', color: '#c7d2fe', formatter: formatNumber, activeCss: 'border-violet-300',},
                {key: 'REG2DAU', visible: false, title: 'Reg. to ' + XAU(props.rangeType), color: '#49de80', formatter: formatPercent, customCss: 'rounded-bl-md', activeCss: 'border-green-400',},
                {key: 'REG2PU', visible: false, title: 'Reg. to Pay', color: '#67e8f9', formatter: formatPercent, activeCss: 'border-sky-400',},
                {key: 'REG2TAU', visible: false, title: 'Reg. to Active', color: '#a5b4fb', formatter: formatPercent, activeCss: 'rounded-br-md border-violet-400',},
            ]),
        },
    ],
};
</script>

<template>
    <div class="grid gap-8 grid-cols-1">
        <StatsChart v-for="chart in charts.per_date" :chart="chart" />
    </div>
    <div class="grid gap-8 grid-cols-1">
        <StatsChart v-for="chart in charts.lifetime" :chart="chart" />
    </div>
</template>
