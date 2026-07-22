<script setup>
import {computed, onMounted, ref} from 'vue';
import {Link, usePage} from '@inertiajs/vue3';
import axios from 'axios';
import DialogModal from '@/Components/DialogModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import {formatCurrency, formatNumber} from '@/Helpers/NumberHelpers.js';
import CashCoinIcon from 'bootstrap-icons/icons/cash-coin.svg?component';
import PersonPlusIcon from 'bootstrap-icons/icons/person-plus-fill.svg?component';
import EyeIcon from 'bootstrap-icons/icons/eye-fill.svg?component';

const page = usePage();

const show = ref(false);
const summary = ref(null);

const metricDefs = {
    net: {label: 'Net Revenue', icon: CashCoinIcon, format: (value) => formatCurrency(value, {}, page.props.teamCurrency)},
    reg: {label: 'New Users', icon: PersonPlusIcon, format: formatNumber},
    visitors: {label: 'Visitors', icon: EyeIcon, format: formatNumber},
};

const tiles = computed(() => Object.entries(metricDefs)
    .filter(([key]) => key in (summary.value?.metrics ?? {}))
    .map(([key, def]) => ({key, ...def, value: summary.value.metrics[key]}))
);

const gridColsClass = computed(() => ({1: 'grid-cols-1', 2: 'grid-cols-2', 3: 'grid-cols-3'}[tiles.value.length] ?? 'grid-cols-3'));

const sinceLabel = computed(() => {
    if (!summary.value) return '';

    const days = Math.round((Date.now() - new Date(summary.value.since).getTime()) / 86400000);

    return `${days} days`;
});

onMounted(async () => {
    if (!page.props.loginSummaryEligible) return;

    const {data} = await axios.get(route('login-summary'));

    if (data.summary) {
        summary.value = data.summary;
        show.value = true;
    }
});
</script>

<template>
    <DialogModal :show="show" max-width="2xl" @close="show = false">
        <template #title>
            <span class="text-2xl font-bold">While you were away 🎉</span>
        </template>

        <template #content>
            <p class="text-base">
                Your last login was {{ sinceLabel }} ago. Here is what happened across all your projects since then:
            </p>

            <div class="mt-8 mb-2 grid divide-x divide-gray-950/5 dark:divide-white/10" :class="gridColsClass">
                <div
                    v-for="tile in tiles"
                    :key="tile.key"
                    class="px-6 first:pl-0 last:pr-0"
                >
                    <component :is="tile.icon" class="size-6 text-indigo-500" />
                    <div class="mt-3 text-3xl font-semibold tabular-nums text-gray-900 dark:text-dark-100">
                        {{ tile.format(tile.value) }}
                    </div>
                    <div class="mt-1 text-sm text-gray-500 dark:text-dark-400 truncate">
                        {{ tile.label }}
                    </div>
                </div>
            </div>

            <p class="mt-8 text-gray-500 dark:text-dark-400">
                Don't want to receive these notifications?
                <Link :href="route('notifications.index')" class="underline hover:text-gray-700 dark:hover:text-dark-200">
                    Change notification settings
                </Link>
            </p>
        </template>

        <template #footer>
            <PrimaryButton @click="show = false">
                Keep it up
            </PrimaryButton>
        </template>
    </DialogModal>
</template>
