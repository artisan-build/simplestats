<script setup>
import {computed, ref} from 'vue';
import {Popover, PopoverButton, PopoverPanel} from '@headlessui/vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import {periodSingular} from '@/Helpers/StringHelpers.js';
import pluralize from 'pluralize';
import CalendarRangeIcon from 'bootstrap-icons/icons/calendar-range.svg?component';
import ChevronUpDownIcon from 'bootstrap-icons/icons/chevron-expand.svg?component';

const props = defineProps({
    rangeStart: String,
    rangeAmount: [String, Number],
    rangeType: String,
    rangeTypes: Array,
});

const emit = defineEmits(['apply']);

const rangeAmounts = Array.from({length: 60}, (_, i) => ({
    id: (i + 1).toString(),
    name: (i + 1).toString(),
}));

const ymd = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

const start = ref(props.rangeStart ?? ymd(new Date()));
const amount = ref(String(props.rangeAmount ?? '7'));
const type = ref(props.rangeType ?? 'days');

// The popover always reopens with the applied filter values, discarding edits
// that were never applied.
const seed = () => {
    start.value = props.rangeStart ?? ymd(new Date());
    amount.value = String(props.rangeAmount ?? '7');
    type.value = props.rangeType ?? 'days';
};

const label = computed(() => {
    const amt = Number(props.rangeAmount ?? 7);
    const unit = pluralize(periodSingular[props.rangeType] ?? 'day', amt);
    const date = props.rangeStart ? new Date(`${props.rangeStart}T00:00:00`) : new Date();
    const sameYear = date.getFullYear() === new Date().getFullYear();
    const dateStr = date.toLocaleString('en-US', sameYear
        ? {month: 'short', day: 'numeric'}
        : {month: 'short', day: 'numeric', year: 'numeric'});

    return `From ${dateStr} · last ${amt} ${unit}`;
});

const apply = (close) => {
    emit('apply', {
        range_start: start.value,
        range_amount: amount.value,
        range_type: type.value,
    });
    close();
};
</script>

<template>
    <Popover class="relative">
        <PopoverButton
            class="relative mt-1 block w-full sm:w-auto cursor-default rounded-md border border-gray-300 dark:border-dark-700 bg-white dark:bg-dark-800 py-2 pl-3 pr-10 text-left shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-gray-900 dark:text-dark-200"
            @click="seed"
        >
            <span class="flex items-center gap-2">
                <CalendarRangeIcon class="size-4 shrink-0 text-gray-400" aria-hidden="true" />
                <span class="block truncate">{{ label }}</span>
            </span>
            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                <ChevronUpDownIcon class="size-5 text-gray-400" aria-hidden="true" />
            </span>
        </PopoverButton>

        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <PopoverPanel
                v-slot="{ close }"
                class="absolute left-0 z-30 mt-2 w-72 max-w-[calc(100vw-2rem)] rounded-md bg-white dark:bg-dark-800 p-4 shadow-lg ring-1 ring-black/5"
            >
                <form class="space-y-4" @submit.prevent="apply(close)">
                    <div>
                        <InputLabel for="custom_range_start" value="From" />
                        <TextInput
                            type="date"
                            id="custom_range_start"
                            v-model="start"
                            class="mt-1 block w-full dark:!bg-dark-900"
                        />
                    </div>
                    <div>
                        <InputLabel for="custom_range_amount" value="Last" />
                        <div class="mt-1 flex gap-2">
                            <SelectInput
                                id="custom_range_amount"
                                v-model="amount"
                                class="block w-20"
                                :options="rangeAmounts"
                            />
                            <SelectInput
                                id="custom_range_type"
                                v-model="type"
                                class="block flex-1"
                                :options="rangeTypes"
                            />
                        </div>
                    </div>
                    <PrimaryButton class="w-full justify-center">Apply</PrimaryButton>
                </form>
            </PopoverPanel>
        </transition>
    </Popover>
</template>
