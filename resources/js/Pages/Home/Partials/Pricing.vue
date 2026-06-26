<script setup>
import {Link} from '@inertiajs/vue3';
import SectionHeader from "@/Pages/Home/Partials/SectionHeader.vue";
import RangeSlider from "@/Components/RangeSlider.vue";
import {computed, ref} from "vue";
import {useLocale} from "@/Composables/useLocale.js";
import {trans} from 'laravel-vue-i18n';
import CreditCardIcon from 'bootstrap-icons/icons/credit-card-fill.svg?component';
import CheckCircleFillIcon from 'bootstrap-icons/icons/check-circle-fill.svg?component';
import XCircleIcon from 'bootstrap-icons/icons/x-circle.svg?component';

const { localizedPath } = useLocale();

const props = defineProps({
    plans: Array,
});

const selectedInterval = ref('monthly');

const getIntervalClass = (interval) => {
    if (selectedInterval.value === interval) {
        return 'focus:outline-none px-3 w-1/2 sm:w-auto py-1 sm:mx-0.5 text-white bg-indigo-500 rounded-md';
    } else {
        return 'focus:outline-none px-3 w-1/2 sm:w-auto py-1 sm:mx-0.5 text-gray-800 dark:text-dark-200 dark:hover:bg-dark-800 bg-transparent rounded-md hover:bg-gray-200';
    }
};

const priceIntervalKey = computed(() => {
    return selectedInterval.value === 'monthly' ? 'price_monthly' : 'price_yearly';
});

const taxIntervalKey = computed(() => {
    return selectedInterval.value === 'monthly' ? 'tax_mode_monthly' : 'tax_mode_yearly';
});

const priceIntervalString = computed(() => {
    return selectedInterval.value === 'monthly' ? 'month' : 'year';
});

const eventsOf = (plan) => Number(plan.options?.Events ?? 0);

// A plan is offered on the slider when it is free (the floor) or has a configured
// price for the active interval. This auto-hides tiers whose Paddle price isn't set up yet.
const availablePlans = computed(() => {
    return [...props.plans]
        .filter((plan) => plan.name === 'Free' || plan[priceIntervalKey.value] > 0)
        .sort((a, b) => eventsOf(a) - eventsOf(b));
});

const initialIndex = (() => {
    const list = [...props.plans]
        .filter((plan) => plan.name === 'Free' || plan.price_monthly > 0)
        .sort((a, b) => eventsOf(a) - eventsOf(b));
    const promoted = list.findIndex((plan) => plan.promoted);

    return promoted >= 0 ? promoted : Math.floor(list.length / 2);
})();

const sliderIndex = ref(initialIndex);

const selectedPlan = computed(() => {
    const list = availablePlans.value;
    const index = Math.min(sliderIndex.value, list.length - 1);

    return list[index] ?? null;
});

const formatEvents = (amount) => {
    return new Intl.NumberFormat('en', { notation: 'compact', maximumFractionDigits: 1 }).format(amount);
};

const sliderMinLabel = computed(() => {
    const first = availablePlans.value[0];

    if (! first) {
        return '';
    }

    const label = formatEvents(eventsOf(first));
    const isFree = first.name === 'Free' || first.price_monthly === 0;

    return isFree ? `${label} (${trans('free')})` : label;
});

const sliderMaxLabel = computed(() => {
    const last = availablePlans.value[availablePlans.value.length - 1];

    return last ? formatEvents(eventsOf(last)) : '';
});

const getPlanPrice = (amount, discount) => {
    amount = amount / 100;

    if (discount) {
        amount = Math.floor(amount * (discount / 100));
    }

    return '$' + amount;
};

const hasExternalTax = computed(() => {
    return props.plans.filter((plan) => plan.tax_mode_monthly === 'external' || plan.tax_mode_yearly === 'external').length > 0;
});

const yearlySavings = computed(() => {
    const plan = selectedPlan.value;

    if (! plan || plan.price_monthly <= 0 || plan.price_yearly <= 0) {
        return null;
    }

    const saved = (plan.price_monthly * 12 - plan.price_yearly) / 100;

    return saved > 0 ? '$' + Math.round(saved) : null;
});
</script>

<template>
    <section class="flex justify-center">
        <div class="max-w-screen-xl w-full">
            <SectionHeader id="pricing" :eyebrow="$t('Plans')">
                {{ $t('Subscribe at your') }}&nbsp;<span class="text-indigo-500 underline">{{ $t('scale') }}</span>
            </SectionHeader>

            <div class="text-center -mt-10 mb-10">
                <div class="relative inline-block">
                    <div class="inline-block mx-auto text-sm overflow-hidden p-0.5 border rounded-lg dark:border-dark-700">
                        <div class="sm:-mx-0.5 flex">
                            <button @click="selectedInterval = 'monthly'" :class="getIntervalClass('monthly')">{{ $t('monthly') }}</button>
                            <button @click="selectedInterval = 'yearly'" :class="getIntervalClass('yearly')">{{ $t('yearly') }}</button>
                        </div>
                    </div>

                    <div class="hidden sm:flex absolute top-1/2 left-full -translate-y-1/2 ml-1 -mt-4 items-center gap-1 text-indigo-600 dark:text-indigo-400">
                        <svg viewBox="0 0 70 50" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="w-11 h-auto shrink-0">
                            <path d="M64 12C46 4 18 8 9 34" />
                            <path d="M9 34L20 31" />
                            <path d="M9 34L13 23" />
                        </svg>
                        <span class="italic font-medium whitespace-nowrap -rotate-3 -mt-6">{{ $t('2 months free') }}</span>
                    </div>
                </div>
            </div>

            <div v-if="selectedPlan" class="px-6">
                <div class="max-w-2xl mx-auto px-6 py-8 sm:px-10 sm:py-10 border border-gray-200 dark:border-dark-700 rounded-2xl">
                    <div class="flex items-start justify-between flex-wrap gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium uppercase tracking-wide text-indigo-500">
                                    {{ $t(selectedPlan.name) }}
                                </span>
                                <span
                                    v-if="selectedPlan.promoted"
                                    class="text-[11px] font-medium uppercase tracking-wide text-indigo-600 bg-indigo-50 dark:text-indigo-400 dark:bg-indigo-500/10 px-2 py-0.5 rounded-full"
                                >
                                    {{ $t('Recommended') }}
                                </span>
                            </div>
                            <div class="mt-1 text-4xl font-semibold text-gray-800 dark:text-dark-100">
                                {{ formatEvents(eventsOf(selectedPlan)) }}
                            </div>
                            <div class="text-gray-600 dark:text-dark-400">{{ $t('monthly events') }}</div>
                        </div>

                        <div class="text-right">
                            <div class="text-3xl font-semibold text-gray-800 dark:text-dark-100">
                                <span v-if="selectedPlan.discount && selectedPlan[priceIntervalKey] > 0">
                                    <span class="line-through text-base text-gray-500">
                                        {{ getPlanPrice(selectedPlan[priceIntervalKey], 0) }}
                                    </span>
                                    {{ getPlanPrice(selectedPlan[priceIntervalKey], selectedPlan.discount) }}
                                </span>
                                <span v-else>
                                    {{ getPlanPrice(selectedPlan[priceIntervalKey], 0) }}
                                </span>
                            </div>
                            <div class="text-base font-normal text-gray-600 dark:text-dark-400">
                                / {{ trans(priceIntervalString) }}<span v-if="selectedPlan[taxIntervalKey] === 'external'">*</span>
                            </div>
                            <div
                                v-if="selectedInterval === 'yearly' && yearlySavings"
                                class="mt-1 inline-block text-sm bg-green-400 px-2 py-1 rounded text-gray-800"
                            >
                                {{ $t('Save :amount', { amount: yearlySavings }) }}
                            </div>
                        </div>
                    </div>

                    <RangeSlider
                        v-model="sliderIndex"
                        :max="availablePlans.length - 1"
                        :min-label="sliderMinLabel"
                        :max-label="sliderMaxLabel"
                        :aria-label="$t('Monthly events')"
                        class="mt-8"
                    />

                    <p class="mt-6 text-gray-800 dark:text-dark-400">
                        {{ $t(selectedPlan.short_description) }}
                    </p>

                    <div class="mt-8 space-y-5">
                        <div v-for="(feature, option) in selectedPlan.features" :key="option">
                            <div v-if="selectedPlan.options[option]" class="flex items-center text-gray-800 dark:text-dark-400">
                                <CheckCircleFillIcon class="size-5 text-indigo-500" />

                                <span class="mx-4">{{ $t(feature) }}</span>
                            </div>
                            <div v-else class="flex items-center text-gray-500">
                                <XCircleIcon class="size-5" />

                                <span class="mx-4">{{ $t(feature) }}</span>
                            </div>
                        </div>
                    </div>

                    <template v-if="$page.props.canRegister">
                        <Link :href="localizedPath('/register')" class="block text-center w-full px-4 py-2 mt-10 font-medium tracking-wide text-white capitalize transition-colors duration-300 transform bg-indigo-500 rounded-md hover:bg-indigo-600 focus:outline-none focus:bg-indigo-600">
                            {{ $t('Get started') }}
                        </Link>
                        <p class="mt-3 text-center text-sm text-gray-500 dark:text-dark-400">
                            <CreditCardIcon class="size-4 inline-block" />
                            {{ $t('No credit card required') }}
                        </p>
                    </template>
                </div>
            </div>

            <div v-if="hasExternalTax" class="mt-10 text-center text-gray-400 px-4 sm:pm-0">
                * {{ $t('All prices are net prices. Local taxes and VAT may be added in the checkout. You can remove VAT by adding your VAT id.') }}
            </div>
            <div class="mt-4 text-base text-center text-gray-600 dark:text-dark-400 px-4 sm:pm-0">
                {{ $t('If these plans do not fit your needs, feel free to') }} <a class="underline" href="mailto:simplestats@laracraft.tech">{{ $t('contact sales') }}</a>.
            </div>
            <div class="mt-4 text-base text-center text-gray-600 dark:text-dark-400 px-4 sm:pm-0">
                {{ $t('Wonder what an') }} <a class="underline" href="#what-is-an-event">{{ $t('event') }}</a> {{ $t('is?') }}
            </div>
        </div>
    </section>
</template>
