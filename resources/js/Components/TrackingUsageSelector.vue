<script setup>
import {computed} from "vue";
import IncognitoIcon from 'bootstrap-icons/icons/incognito.svg?component';
import PersonCircleIcon from 'bootstrap-icons/icons/person-circle.svg?component';
import CoinIcon from 'bootstrap-icons/icons/coin.svg?component';
import Wallet2Icon from 'bootstrap-icons/icons/wallet2.svg?component';

const props = defineProps({
    modelValue: Number,
    trackingUsages: Object,
    stacked: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const iconSize = computed(() => props.stacked ? 'size-6' : 'size-8');

let bits = props.modelValue;

const recurringBit = props.trackingUsages.recurring?.bit ?? 0;
const usersBit = props.trackingUsages.users?.bit ?? 0;
const paymentsBit = props.trackingUsages.payments?.bit ?? 0;
const recurringPrereq = usersBit | paymentsBit;

const selectUsageType = (bit) => {
    bits ^= bit;

    // Recurring requires registrations + payments: enabling it pulls those in,
    // removing either one clears it again.
    if (bit === recurringBit && (bits & recurringBit)) {
        bits |= recurringPrereq;
    } else if ((bit === usersBit || bit === paymentsBit) && ((bits & recurringPrereq) !== recurringPrereq)) {
        bits &= ~recurringBit;
    }

    emit('update:modelValue', bits);
}

</script>

<template>
    <div class="grid gap-6 text-left" :class="(stacked) ? 'grid-cols-2' : 'grid-cols-1 sm:grid-cols-2'">
        <div v-for="(track, type) in trackingUsages"
             @click="selectUsageType(track.bit)"
             class="cursor-pointer select-none rounded-xl border-2 hover:border-indigo-200 dark:hover:border-gray-400 transition"
             :class="[(track.bit & bits) ? '!border-indigo-400' : '', (stacked) ? 'p-2' : 'p-3']"
        >
            <div class="flex items-center" :class="(stacked) ? 'gap-x-2' : 'gap-x-4'">
                <div>
                    <div v-if="type === 'visitors'">
                        <IncognitoIcon :class="iconSize" class="text-indigo-400" />
                    </div>
                    <div v-else-if="type === 'users'">
                        <PersonCircleIcon :class="iconSize" class="text-indigo-400" />
                    </div>
                    <div v-if="type === 'payments'">
                        <CoinIcon :class="iconSize" class="text-indigo-400" />
                    </div>
                    <div v-if="type === 'recurring'">
                        <Wallet2Icon :class="iconSize" class="text-indigo-400" />
                    </div>
                </div>
                <div class="flex justify-between w-full">
                    <div :class="(stacked) ? 'text-base' : 'text-lg font-medium'">
                        {{ track.name }}
                    </div>
                    <div>
                        <input
                            type="radio"
                            :checked="track.bit & bits"
                            class="text-indigo-500 bg-gray-100 border-gray-300 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-indigo-500 focus:ring-2 dark:focus:ring-indigo-500 dark:ring-offset-gray-800"
                        />
                    </div>
                </div>
            </div>
            <div v-if=" !stacked" class="flex items-center mt-3 text-sm">
                <div>
                    {{ track.descr }}
                </div>
            </div>
        </div>
    </div>
</template>
