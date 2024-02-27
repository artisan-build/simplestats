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
                        initialVisible: field.initialVisible,
                        color: field.color ?? '',
                        // extras...
                        key: field.key,
                        formatter: field.formatter,
                        customClass: field.customClass ?? '',
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
            name: 'New Revenue',
            formatter: formatCurrency,
            data: extractStatsData4Chart('per_date', [
                {key: 'NET', initialVisible: true, title: 'Net Revenue', color: '#93c5fd', customClass: 'rounded-bl-md',},
                {key: 'GROSS', initialVisible: false, title: 'Gross Revenue', color: '#60a5fa',},
                // {key: 'FEE', initialVisible: false, title: 'Fees', color: '#ffb3ba',},
                {key: 'ARPDAU', initialVisible: false, title: 'ARP' + XAU(props.rangeType), color: '#fce04c', customClass: 'rounded-br-md',},
            ]),
        },
        {
            name: 'New Users',
            formatter: formatNumber,
            data: extractStatsData4Chart('per_date', [
                {key: 'REG', initialVisible: true, title: 'New Regs.', color: '#87efac', customClass: 'rounded-bl-md',},
                {key: 'NAU', initialVisible: false, title: 'NAU', color: '#22c55d',},
                {key: 'NAPU', initialVisible: false, title: 'NAPU', color: '#17803d', customClass: 'rounded-br-md',},
            ]),
        },
        {
            name: XAU(props.rangeType, true),
            formatter: formatNumber,
            data: extractStatsData4Chart('per_date', [
                {key: 'DAU', initialVisible: true, title: XAU(props.rangeType), color: '#fbd44c', customClass: 'rounded-bl-md',},
                {key: 'DAPU', initialVisible: false, title: XAPU(props.rangeType), color: '#fbbf24',},
                {key: 'DAAPU', initialVisible: false, title: XAAPU(props.rangeType), color: '#f59e0c', customClass: 'rounded-br-md',},
            ]),
        },
    ],
    lifetime: [
        {
            name: 'Lifetime Revenue',
            formatter: formatCurrency,
            data: extractStatsData4Chart('lifetime', [
                {key: 'NET', initialVisible: true, title: 'Net Revenue', color: '#93c5fd', customClass: 'rounded-bl-md',},
                // {key: 'GROSS', initialVisible: false, title: 'Gross Revenue', color: '#60a5fa',},
                {key: 'ARPU', initialVisible: false, title: 'ARPU', color: '#facc14',},
                {key: 'ARPPU', initialVisible: false, title: 'ARPPU', color: '#ebb305', customClass: 'rounded-br-md',},
            ]),
        },
        {
            name: 'Lifetime Users',
            formatter: formatNumber,
            data: extractStatsData4Chart('lifetime', [
                {key: 'REG', initialVisible: true, title: 'Total Regs.', color: '#87efac', customClass: 'rounded-bl-md',},
                {key: 'PU', initialVisible: false, title: 'Paying Users', color: '#a6f3fc',},
                {key: 'TAU', initialVisible: false, title: 'Total Actives', color: '#c7d2fe', customClass: 'rounded-br-md',},
            ]),
        },
        {
            name: 'Reg. Conversion',
            formatter: formatPercent,
            data: extractStatsData4Chart('lifetime', [
                {key: 'REG2DAU', initialVisible: true, title: 'Reg. to ' + XAU(props.rangeType), color: '#49de80', customClass: 'rounded-bl-md',},
                {key: 'REG2PU', initialVisible: false, title: 'Reg. to Pay', color: '#67e8f9',},
                {key: 'REG2TAU', initialVisible: false, title: 'Reg. to Active', color: '#a5b4fb', customClass: 'rounded-br-md',},
            ]),
        },
    ],
};
</script>

<template>
    <div class="grid gap-8 grid-cols-1 lg:grid-cols-2 2xl:grid-cols-3">
        <StatsChart v-for="chart in charts.per_date" :chart="chart" />
        <StatsChart v-for="chart in charts.lifetime" :chart="chart" />
    </div>
</template>
