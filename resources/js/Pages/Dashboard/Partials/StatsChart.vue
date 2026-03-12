<script setup>
import {defineComponent, ref} from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import {cent2Currency, formatDate, formatMap} from "@/Helpers/NumberHelpers.js";
import {router, usePage} from "@inertiajs/vue3";
import StatsIndicator from "@/Pages/Dashboard/Partials/StatsIndicator.vue";
import axios from 'axios';
import {
    differenceInDays,
    differenceInHours,
    differenceInMonths,
    endOfDay,
    endOfHour,
    endOfMonth,
    endOfWeek,
    endOfYear,
    format,
    getDaysInMonth,
    isAfter,
    startOfDay,
    startOfMonth,
    startOfWeek,
    startOfYear
} from "date-fns";

const ApexChart = defineComponent(VueApexCharts);

const props = defineProps({
    chart: Object,
    chartLength: Number,
    chartIndex: Number,
    filters: Object,
});

const page = usePage();

const chartInstance = ref(null);
const series = ref(props.chart.data.series);
const categories = props.chart.data.categories;

let tmpSeriesIndex = Number(page.props.auth.user.settings.selectedSeries?.[props.chartIndex] ?? 0);
const selectedSerieIndex = ref((series.value[tmpSeriesIndex] ? tmpSeriesIndex : 0));

const formatter = () => {
    return props.chart.group[selectedSerieIndex.value].formatter;
};

const newFilterForDataPoint = (clickedDate) => {
    let dateFormat = 'y-MM-dd';
    let newFilter = {
        preset: 'custom',
    };

    if (props.filters.range_type === 'years') {
        newFilter.range_type = 'months';
        let endOfYearDate = endOfYear(clickedDate);

        if ((isAfter(endOfYearDate, new Date()))) {
            newFilter.range_start = format(endOfMonth(new Date()), dateFormat);
            newFilter.range_amount = differenceInMonths(new Date(), startOfYear(clickedDate))+1;
        } else {
            newFilter.range_start = format(endOfYearDate, dateFormat);
            newFilter.range_amount = 12;
        }
    } else if (props.filters.range_type === 'months') {
        newFilter.range_type = 'days';
        let endOfMonthDate = endOfMonth(clickedDate);

        if ((isAfter(endOfMonthDate, new Date()))) {
            newFilter.range_start = format(endOfDay(new Date()), dateFormat);
            newFilter.range_amount = differenceInDays(new Date(), startOfMonth(clickedDate))+1;
        } else {
            newFilter.range_start = format(endOfMonthDate, dateFormat);
            newFilter.range_amount = getDaysInMonth(endOfMonthDate);
        }
    } else if (props.filters.range_type === 'weeks') {
        newFilter.range_type = 'days';
        let endOfWeekDate = endOfWeek(clickedDate);

        if ((isAfter(endOfWeekDate, new Date()))) {
            newFilter.range_start = format(endOfDay(new Date()), dateFormat);
            newFilter.range_amount = differenceInDays(new Date(), startOfWeek(clickedDate)) + 1;
        } else {
            newFilter.range_start = format(endOfWeekDate, dateFormat);
            newFilter.range_amount = 7;
        }
    } else if (props.filters.range_type === 'days') {
        newFilter.range_type = 'hours';
        let endOfDayDate = endOfDay(clickedDate);

        if ((isAfter(endOfDayDate, new Date()))) {
            newFilter.range_start = format(endOfHour(new Date()), dateFormat);
            newFilter.range_amount = differenceInHours(new Date(), startOfDay(clickedDate))+1;
        } else {
            newFilter.range_start = format(endOfDayDate, dateFormat);
            newFilter.range_amount = 24;
        }
    }

    return newFilter;
}

const isDarkMode = ref(document.documentElement.classList.contains('dark'));

const chartOptions = ref({
    chart: {
        events: {
            click: function(event, chartContext, opts) {
                let clickedDate = series.value[selectedSerieIndex.value].dates[opts.dataPointIndex];

                if (clickedDate) {
                    let newFilter = newFilterForDataPoint(clickedDate);

                    if ((Object.keys(newFilter).length > 0)) {
                        router.get(route('dashboard'), { ...props.filters, ...newFilter }, {preserveScroll: true})
                    }
                }
            }
        },
        height: 250,
        type: 'area',
        zoom: {
            enabled: false,
        },
        toolbar: {
            show: false,
            offsetY: 12,
        },
        background: isDarkMode.value ? '#262626' : '#FFFFFF',
    },
    title: {
        text: props.chart.name,
        align: 'left',
        offsetY: 12,
        style: {
            fontSize: '17px',
            fontWeight: '600',
            fontFamily: 'Figtree, ui-sans-serif',
            color: isDarkMode.value ? '#AAAAAA' : '#374151',
        },
    },
    theme: {
        mode: isDarkMode.value ? 'dark' : 'light' // Native Unterstützung für Dark Mode
    },
    legend: {
        show: false,
    },
    // colors: [increaseType.color],
    dataLabels: {
        enabled: false
    },
    stroke: {
        width: 2,
        curve: 'smooth',
        // curve: 'straight',
    },
    xaxis: {
        type: 'string',
        categories: categories,
        tickAmount: 5,
        labels: {
            rotate: 0,
        },
        tooltip: {
            enabled: false,
        },
    },
    yaxis: {
        opposite: true,
        labels: {
            formatter: function (value) {
                let format = '';

                if (formatter() === 'percent') {
                    format = formatMap[formatter()](value, {
                        minimumFractionDigits: 1,
                        maximumFractionDigits: 1,
                    }, page.props.teamCurrency);
                } else if (formatter() === 'currency') {
                    format = formatMap[formatter()](value, {
                        minimumFractionDigits: (cent2Currency(value) >= 1000) ? 1 : 0,
                        maximumFractionDigits: (cent2Currency(value) >= 1000) ? 1 : 0,
                    }, page.props.teamCurrency);
                } else {
                    format = formatMap[formatter()](value, {
                        minimumFractionDigits: (value >= 1000) ? 1 : 0,
                        maximumFractionDigits: (value >= 1000) ? 1 : 0,
                    }, page.props.teamCurrency);
                }

                return format;
            }
        }
    },
    tooltip: {
        y: {
            formatter: function(value, options) {
                let format = '';

                if (formatter() === 'number') {
                    format = formatMap[formatter()](value, {
                        minimumFractionDigits: (value >= 1000) ? 2 : 0,
                        maximumFractionDigits: (value >= 1000) ? 2 : 0,
                    }, page.props.teamCurrency);
                } else {
                    format = formatMap[formatter()](value, {
                        minimumFractionDigits: (value === 0) ? 0 : 2,
                        maximumFractionDigits: (value === 0) ? 0 :2,
                    }, page.props.teamCurrency);
                }

                // console.log(selectedSeries, options.seriesIndex)
                if (series.value[0].datesPrev.length && options.seriesIndex === 1) {
                    let prevDate = series.value[0].datesPrev[options.dataPointIndex];
                    let prevDateFormatted = formatDate(prevDate, props.filters.range_type);

                    if (props.filters.comparison === 'year' && props.filters.range_type !== 'months') {
                        prevDateFormatted += ', ' + new Date(prevDate).getFullYear();
                    }

                    format += ` (${prevDateFormatted})`;
                }

                return format;
            }
        }
    },
    grid: {
        show: true,
        // borderColor: '#f1f1f1',
        borderColor: isDarkMode.value ? '#3f3f46' : '#E5E7EB', // Linienfarbe für Dark/Light Mode
    },
});

if (props.chartLength <= 1) {
    chartOptions.value.title.text = 'Chart';
}

const latestPeriod = categories[categories.length - 1];
const previousPeriod = categories[categories.length - 2];
const currentPeriod = formatDate(page.props.rangeCurrent, page.props.filters.range_type);

let hasCurrentPeriod = latestPeriod === currentPeriod;

if (hasCurrentPeriod) {
    chartOptions.value.annotations = {
        xaxis: [
            {
                x: previousPeriod,
                x2: latestPeriod,
                fillColor: isDarkMode.value ? '#444444' : '#DDDDDD',
            }
        ]
    };
}

const selectSeries = (selectedSerie) => {
    selectedSerieIndex.value = selectedSerie.index;

    axios(route('stats.set-series', {
        chartIndex: props.chartIndex,
        seriesIndex: selectedSerie.index,
    }), {
        method: 'POST',
    });
};

const selectedSeries = () => {
    let tmpSelectedSeries = series.value.filter((serie, index) => index === selectedSerieIndex.value);

    if (tmpSelectedSeries[0].dataPrev.length) {
        tmpSelectedSeries[1] = { ...tmpSelectedSeries[0] };
        tmpSelectedSeries[1].data = tmpSelectedSeries[1].dataPrev;
        tmpSelectedSeries[1].dates = tmpSelectedSeries[1].datesPrev;
        tmpSelectedSeries[1].name = tmpSelectedSeries[1].namePrev;
        tmpSelectedSeries[1].nameSmall = tmpSelectedSeries[1].nameSmallPrev;
        tmpSelectedSeries[1].color = tmpSelectedSeries[1].colorPrev;
    }

    return tmpSelectedSeries;
};
</script>

<template>
    <div class="@container bg-white dark:bg-dark-800 border rounded-md" :class="chart.class">
        <div class="hidden grid-cols-1 grid-cols-2 grid-cols-3"></div>
        <div :class="'grid gap-3 grid-cols-'+series.length+' pt-3 px-3'">
            <StatsIndicator
                v-for="(serie, serieIndex) in series"
                :serie="serie"
                :serieIndex="serieIndex"
                :selectedSerieIndex="selectedSerieIndex"
                :hasCurrent="hasCurrentPeriod"
                @selectSeries="selectSeries"
            />
        </div>
        <div class="pl-2 pr-0.5 relative">
            <div v-tooltip.right="{ content: `<div class='flex flex-col space-y-4 max-w-64 p-2 text-sm'>${chart.info}</div>`, html: true }" class="absolute top-3 right-2 text-gray-400 z-10" data-tour="chart-info">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
            </div>
            <ApexChart
                ref="chartInstance"
                type="area"
                :height="chartOptions.chart.height"
                :options="chartOptions"
                :series="selectedSeries()"
            />
        </div>
    </div>
</template>

<style scoped>
.vue-apexcharts {
    cursor: pointer;
}

@media (max-width: 1023px) {
    :deep(.apexcharts-xaxis) {
        display: none;
    }
}
</style>
