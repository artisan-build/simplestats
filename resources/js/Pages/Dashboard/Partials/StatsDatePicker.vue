<script setup>
import VueDatePicker from "@vuepic/vue-datepicker";
import {ref} from "vue";
import {
    endOfMonth,
    endOfYear,
    isSameDay,
    startOfMonth,
    startOfYear,
    subDays,
    subMonths,
    subYears
} from "date-fns";
import '@vuepic/vue-datepicker/dist/main.css'
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    rangeStart: String,
});

const page = usePage();

const emit = defineEmits(['update-dates']);

const date = ref(props.rangeStart);

const presetDates = ref([
    { label: 'Today', value: [new Date(), new Date()]},
    { label: 'Yesterday', value: [subDays(new Date(), 1), subDays(new Date(), 1)]},
    { label: 'Last 7 Days', value: [subDays(new Date(), 7), new Date()]},
    { label: 'Last 30 Days', value: [subDays(new Date(), 30), new Date()]},
    { label: 'Last 365 Days', value: [subDays(new Date(), 365), new Date()]},
    { label: 'This Month', value: [startOfMonth(new Date()), new Date()]},
    { label: 'Last Month', value: [startOfMonth(subMonths(new Date(), 1)), endOfMonth(subMonths(new Date(), 1))]},
    { label: 'This Year', value: [startOfYear(new Date()), new Date()]},
    { label: 'Last Year', value: [startOfYear(subYears(new Date(), 1)), endOfYear(subYears(new Date(), 1))]},
    { label: 'All Time', value: [page.props.statsStartingAt, new Date()]},
]);

const handleDateChanged = (date) => {
    date.value = date;
    emit('update-date', date);
}
</script>

<template>
    <VueDatePicker
        :style="`width: 150px;`"
        v-model="date"
        auto-apply
        :clearable="false"
        :max-date="new Date()"
        :preset-dates="presetDates"
        :enable-time-picker="false"
        @update:model-value="handleDateChanged"
        :ui="{
            input: `!text-center !pr-3 !border-gray-300 !dark:border-gray-700 !dark:bg-gray-900 !dark:text-gray-300 !focus:border-indigo-500 !dark:focus:border-indigo-600 !focus:ring-indigo-500 !dark:focus:ring-indigo-600 !rounded-md !shadow-sm !mt-1 !block`,
            menu: '!p-3',
        }"
    >
        <template #preset-date-range-button="{ label, value, presetDate }">
            <span
                role="button"
                :tabindex="0"
                @click="presetDate(value)"
                @keyup.enter.prevent="presetDate(value)"
                @keyup.space.prevent="presetDate(value)">
              {{ label }}
            </span>
        </template>
    </VueDatePicker>
</template>

<style>
.dp__theme_light {
    --dp-primary-color: theme('colors.indigo.500');
}

.dp__input {
    height: 42px !important;
}

.dp__btn {
    padding: 5px 7px !important;
    margin: 4px 0 !important;
}

.dp--preset-dates {
    padding-right: 15px !important;
}

.dp__instance_calendar {
    padding: 0 10px;
}
</style>

