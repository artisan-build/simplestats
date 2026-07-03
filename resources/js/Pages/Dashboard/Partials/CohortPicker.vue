<script setup>
import VueDatePicker from "@vuepic/vue-datepicker";
import '@vuepic/vue-datepicker/dist/main.css';
import {computed, ref} from "vue";
import {
    endOfMonth,
    endOfQuarter,
    endOfYear,
    startOfMonth,
    startOfQuarter,
    startOfYear,
    subMonths,
    subYears
} from "date-fns";

const props = defineProps({
    filters: Object,
});

const emit = defineEmits(['apply', 'clear']);

const isDark = ref(document.documentElement.classList.contains('dark'));

const ymd = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
const addDays = (date, amount) => {
    const next = new Date(date);
    next.setDate(next.getDate() + amount);
    return next;
};

// The stored cohort_end is the exclusive upper bound; the calendar shows the
// inclusive last day, so the two convert by a single day in either direction.
const initialRange = (props.filters.cohort_start && props.filters.cohort_end)
    ? [
        new Date(`${props.filters.cohort_start.slice(0, 10)}T00:00:00`),
        addDays(new Date(`${props.filters.cohort_end.slice(0, 10)}T00:00:00`), -1),
    ]
    : null;

const range = ref(initialRange);

const isActive = computed(() => Boolean(range.value && range.value[0] && range.value[1]));

// Calendar-aligned windows, so cohorts stay comparable month-over-month etc.
const presetDates = ref([
    {label: 'This month', value: [startOfMonth(new Date()), new Date()]},
    {label: 'Last month', value: [startOfMonth(subMonths(new Date(), 1)), endOfMonth(subMonths(new Date(), 1))]},
    {label: 'This quarter', value: [startOfQuarter(new Date()), endOfQuarter(new Date())]},
    {label: 'This year', value: [startOfYear(new Date()), new Date()]},
    {label: 'Last year', value: [startOfYear(subYears(new Date(), 1)), endOfYear(subYears(new Date(), 1))]},
]);

const formatLabel = (dates) => {
    const [start, end] = dates;
    const sameYear = start.getFullYear() === end.getFullYear();

    const startStr = start.toLocaleString('en-US', sameYear
        ? {day: 'numeric', month: 'short'}
        : {day: 'numeric', month: 'short', year: 'numeric'});
    const endStr = end.toLocaleString('en-US', {day: 'numeric', month: 'short', year: 'numeric'});

    return `${startStr} – ${endStr}`;
};

const onChange = (dates) => {
    if (! dates || ! dates[0] || ! dates[1]) {
        emit('clear');
        return;
    }

    const [start, end] = dates;

    emit('apply', {
        cohort_start: `${ymd(start)} 00:00:00`,
        cohort_end: `${ymd(addDays(end, 1))} 00:00:00`,
        cohort_label: formatLabel(dates),
    });
};
</script>

<template>
    <VueDatePicker
        v-model="range"
        range
        multi-calendars
        auto-apply
        :dark="isDark"
        placeholder="Cohort"
        :format="formatLabel"
        :max-date="new Date()"
        :enable-time-picker="false"
        :preset-dates="presetDates"
        @update:model-value="onChange"
        :class="['cohort-picker mt-1', { 'cohort-picker-active': isActive }]"
        :ui="{ menu: '!p-3' }"
    >
        <template #preset-date-range-button="{ label, value, presetDate }">
            <span
                role="button"
                :tabindex="0"
                @click="presetDate(value)"
                @keyup.enter.prevent="presetDate(value)"
                @keyup.space.prevent="presetDate(value)"
            >
                {{ label }}
            </span>
        </template>
    </VueDatePicker>
</template>

<style>
.cohort-picker {
    width: 100%;
}

@media (min-width: 640px) {
    .cohort-picker {
        width: 220px;
    }
}

.cohort-picker .dp__input {
    height: 42px !important;
}

.cohort-picker-active .dp__input {
    background-color: theme('colors.indigo.100');
    border-color: theme('colors.indigo.300');
}

.dark .cohort-picker-active .dp__input {
    background-color: theme('colors.indigo.500');
    border-color: theme('colors.indigo.600');
}

.cohort-picker .dp--clear-btn svg {
    fill: theme('colors.gray.600');
}

.cohort-picker .dp--clear-btn:hover svg {
    fill: theme('colors.gray.800');
}

.dp__theme_light {
    --dp-primary-color: theme('colors.indigo.500');
}

.dp__theme_dark {
    --dp-primary-color: theme('colors.indigo.500');
}
</style>
