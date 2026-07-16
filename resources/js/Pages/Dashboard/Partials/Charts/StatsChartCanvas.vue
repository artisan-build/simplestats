<script setup>
import {defineComponent, onBeforeUnmount, onMounted, ref} from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import {cent2Currency, formatDate, formatMap} from "@/Helpers/NumberHelpers.js";
import {router, usePage} from "@inertiajs/vue3";
import InfoTooltip from "@/Components/InfoTooltip.vue";
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
    // Already resolved series to plot (1 entry, or 2 when a previous-period twin is present).
    // series[0] is the primary serie and drives the click/tooltip lookups.
    series: Array,
    categories: Array,
    formatter: String,
    decimals: {type: Number, default: null},
    name: {type: String, default: ''},
    info: {type: String, default: ''},
    filters: Object,
    height: {type: Number, default: 250},
    clickable: {type: Boolean, default: true},
    navigateRoute: {type: String, default: 'dashboard'},
    navigateParams: {type: Object, default: () => ({})},
    // Caps the number of x axis date labels (first and last always included,
    // the rest evenly spread). For narrow small-multiple cards. Null keeps
    // Apex' default tick distribution.
    maxXLabels: {type: Number, default: null},
});

// Picks the category indices to label: first and last always, the rest evenly
// spread. Falls back to fewer labels when the spread would put two labels
// directly next to each other (e.g. 6 points -> 3 labels instead of 4).
const visibleXLabels = () => {
    const count = props.categories.length;

    if (count <= props.maxXLabels) {
        return new Set(props.categories);
    }

    let indices = [0, count - 1];

    for (let amount = props.maxXLabels; amount > 2; amount--) {
        const spread = Array.from({length: amount}, (_, i) => Math.round(i * (count - 1) / (amount - 1)));
        const minGap = Math.min(...spread.slice(1).map((index, i) => index - spread[i]));

        if (minGap >= 2) {
            indices = spread;
            break;
        }
    }

    return new Set(indices.map((index) => props.categories[index]));
};

const xLabels = props.maxXLabels ? visibleXLabels() : null;

const page = usePage();

const chartInstance = ref(null);

// Auf Touch-Geräten feuert ApexCharts' click-Event auch beim Scrollen. Wir merken
// uns, ob der Finger sich zwischen touchstart und touchend nennenswert bewegt hat,
// und werten das dann im click-Handler als Scroll (kein Klick auf den Datenpunkt).
let touchMoved = false;
let touchStartX = 0;
let touchStartY = 0;
const TOUCH_MOVE_THRESHOLD = 10;

const onTouchStart = (event) => {
    touchMoved = false;
    const touch = event.touches[0];
    touchStartX = touch.clientX;
    touchStartY = touch.clientY;
};

const onTouchMove = (event) => {
    const touch = event.touches[0];

    if (Math.abs(touch.clientX - touchStartX) > TOUCH_MOVE_THRESHOLD || Math.abs(touch.clientY - touchStartY) > TOUCH_MOVE_THRESHOLD) {
        touchMoved = true;
    }
};

onMounted(() => {
    const el = chartInstance.value?.$el;

    if (el) {
        el.addEventListener('touchstart', onTouchStart, {passive: true});
        el.addEventListener('touchmove', onTouchMove, {passive: true});
    }
});

onBeforeUnmount(() => {
    const el = chartInstance.value?.$el;

    if (el) {
        el.removeEventListener('touchstart', onTouchStart);
        el.removeEventListener('touchmove', onTouchMove);
    }
});

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
                if (!props.clickable || touchMoved) {
                    return;
                }

                let clickedDate = props.series[0].dates[opts.dataPointIndex];

                if (clickedDate) {
                    let newFilter = newFilterForDataPoint(clickedDate);

                    if ((Object.keys(newFilter).length > 0)) {
                        router.get(route(props.navigateRoute, props.navigateParams), { ...props.filters, ...newFilter }, {preserveScroll: true})
                    }
                }
            }
        },
        height: props.height,
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
        text: props.name,
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
    dataLabels: {
        enabled: false
    },
    stroke: {
        width: 2,
        curve: 'smooth',
    },
    xaxis: {
        type: 'string',
        categories: props.categories,
        // With maxXLabels the label filtering happens in the formatter, so every
        // category must get a tick slot (tickAmount would re-thin our selection).
        tickAmount: props.maxXLabels ? undefined : 5,
        labels: {
            rotate: 0,
            formatter: props.maxXLabels
                ? (value) => xLabels.has(value) ? value : ''
                : undefined,
        },
        axisTicks: {
            show: !props.maxXLabels,
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

                if (props.formatter === 'percent') {
                    format = formatMap[props.formatter](value, {
                        minimumFractionDigits: 1,
                        maximumFractionDigits: 1,
                    }, page.props.teamCurrency);
                } else if (props.formatter === 'currency') {
                    let dec = props.decimals;

                    format = formatMap[props.formatter](value, {
                        minimumFractionDigits: dec ?? ((cent2Currency(value) >= 1000) ? 1 : 0),
                        maximumFractionDigits: dec ?? ((cent2Currency(value) >= 1000) ? 1 : 0),
                    }, page.props.teamCurrency);
                } else {
                    format = formatMap[props.formatter](value, {
                        minimumFractionDigits: (value >= 1000) ? 1 : 0,
                        maximumFractionDigits: (value >= 1000) ? 1 : 0,
                    }, page.props.teamCurrency);
                }

                return format;
            }
        }
    },
    tooltip: {
        // The x axis label formatter blanks most categories in maxXLabels mode
        // and would blank the tooltip title too, so resolve it independently.
        x: {
            formatter: (value, {dataPointIndex}) => props.categories[dataPointIndex] ?? value,
        },
        y: {
            formatter: function(value, options) {
                let format = '';

                if (props.formatter === 'number') {
                    let dec = props.decimals ?? ((value >= 1000) ? 2 : 0);

                    format = formatMap[props.formatter](value, {
                        minimumFractionDigits: dec,
                        maximumFractionDigits: dec,
                    }, page.props.teamCurrency);
                } else {
                    let dec = props.decimals ?? 2;

                    format = formatMap[props.formatter](value, {
                        minimumFractionDigits: (value === 0) ? 0 : dec,
                        maximumFractionDigits: (value === 0) ? 0 : dec,
                    }, page.props.teamCurrency);
                }

                if (props.series[0].datesPrev.length && options.seriesIndex === 1) {
                    let prevDate = props.series[0].datesPrev[options.dataPointIndex];
                    let prevDateFormatted = formatDate(prevDate, props.filters.range_type);

                    // Only the day format omits the year and is safely Date-parseable; week/month/year
                    // labels already carry the year (and aren't valid Date strings, so getFullYear is NaN).
                    if (props.filters.comparison === 'year' && props.filters.range_type === 'days') {
                        prevDateFormatted += ', ' + new Date(prevDate).getFullYear();
                    }

                    format += ` (${prevDateFormatted})`;
                }

                if (options.seriesIndex === 0) {
                    let selected = props.series[0];

                    if (selected?.tooltipExtra) {
                        let parts = selected.tooltipExtra.map(function (extra) {
                            let value = extra.data[options.dataPointIndex];

                            if (value === undefined || value === null) {
                                return null;
                            }

                            return `${extra.label} ${formatMap[extra.formatter](value, {minimumFractionDigits: 0, maximumFractionDigits: 0}, page.props.teamCurrency)}`;
                        }).filter(Boolean);

                        if (parts.length) {
                            format += ` (${parts.join(' · ')})`;
                        }
                    }
                }

                return format;
            }
        }
    },
    grid: {
        show: true,
        borderColor: isDarkMode.value ? '#3f3f46' : '#E5E7EB', // Linienfarbe für Dark/Light Mode
        // The first date label is centered under the first data point and would
        // get clipped at the svg edge without extra room.
        ...(props.maxXLabels ? {padding: {left: 35}} : {}),
    },
});

const latestPeriod = props.categories[props.categories.length - 1];
const previousPeriod = props.categories[props.categories.length - 2];
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
</script>

<template>
    <div class="pl-2 pr-0.5 relative" :class="{ 'cursor-pointer': clickable }">
        <div v-if="info" class="absolute top-3 right-3 z-10" data-tour="chart-info">
            <InfoTooltip :html="info" placement="right" iconClass="size-5" />
        </div>
        <ApexChart
            ref="chartInstance"
            type="area"
            :height="chartOptions.chart.height"
            :options="chartOptions"
            :series="series"
        />
    </div>
</template>

<style scoped>
@media (max-width: 1023px) {
    :deep(.apexcharts-xaxis) {
        display: none;
    }
}
</style>
