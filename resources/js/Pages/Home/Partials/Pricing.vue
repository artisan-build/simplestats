<script setup>
import {Link} from '@inertiajs/vue3';
import SectionHeader from "@/Pages/Home/Partials/SectionHeader.vue";
import {computed, ref} from "vue";

defineProps({
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

const getPlanPrice = (amount, discount) => {
    amount = amount  / 100;

    if (discount) {
        amount = Math.floor(amount * (discount / 100));
    }

    return '$' + amount;
}
</script>

<template>
    <section class="flex justify-center">
        <div class="max-w-screen-xl">
            <SectionHeader id="pricing">
                Subscribe at your&nbsp;<span class="text-indigo-500">scale</span>
            </SectionHeader>

            <div class="text-center -mt-10 mb-10">
                <div class="inline-block mx-auto text-sm overflow-hidden p-0.5 border rounded-lg dark:border-dark-700">
                    <div class="sm:-mx-0.5 flex">
                        <button @click="selectedInterval = 'monthly'" :class="getIntervalClass('monthly')">monthly</button>
                        <button @click="selectedInterval = 'yearly'" :class="getIntervalClass('yearly')">yearly</button>
                    </div>
                </div>
            </div>

            <!-- the JIT Compiler from tailwindcss cant recognize things like this "md:grid-cols-'+plans.length/2+'" so we need to put the classes here manually so that the exists --->
            <div class="hidden md:grid-cols-2 md:grid-cols-3 md:grid-cols-4 lg:grid-cols-2 lg:grid-cols-3 lg:grid-cols-4"></div>
            <div class="grid gap-x-8  grid gap-y-10 lg:gap-y-20 px-6" :class="'md:grid-cols-'+plans.length/2+' lg:grid-cols-'+plans.length">
                <div v-for="plan in plans"
                     class="px-6 py-6 transition-colors duration-300 transform rounded-lg dark:hover:bg-dark-800"
                     :class="plan.promoted ? 'bg-gray-800 dark:bg-dark-800' : ''"
                >
                    <div class="flex justify-between flex-wrap text-2xl font-medium dark:text-dark-100" :class="plan.promoted ? 'text-gray-100' : 'text-gray-800'">
                        <div>{{ plan.name }}</div>
                        <div v-if="selectedInterval === 'yearly' && plan.yearly_incentive !== 0">
                            <div class="text-sm bg-green-400 px-2 py-1 rounded text-gray-800">
                                {{ plan.yearly_incentive }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-2 text-3xl font-semibold" :class="plan.promoted ? 'text-gray-100' : 'text-gray-800 dark:text-dark-100'">
                        <span v-if="plan.discount && plan[priceIntervalKey] > 0">
                            <span class="line-through text-base">
                                {{ getPlanPrice(plan[priceIntervalKey], 0) }}
                            </span>
                            {{ getPlanPrice(plan[priceIntervalKey], plan.discount) }}
                        </span>
                        <span v-else>
                            {{ getPlanPrice(plan[priceIntervalKey], 0) }}
                        </span>
                        <span class="text-base font-normal dark:text-dark-400" :class="plan.promoted ? 'text-gray-400' : 'text-gray-600'">
                            / {{ priceIntervalString }}<span v-if="plan[taxIntervalKey] === 'external'">*</span>
                        </span>
                    </div>

                    <p class="mt-4" :class="plan.promoted ? 'text-gray-100' : 'text-gray-800 dark:text-dark-400'">
                        {{ plan.short_description }}
                    </p>

                    <div class="mt-8 space-y-8">
                        <div v-for="(feature, option) in plan.features">
                            <div v-if="plan.options[option]" class="flex items-center" :class="plan.promoted ? 'text-gray-100' : 'text-gray-800 dark:text-dark-400'">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-indigo-500">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>

                                <span class="mx-4" :class="plan.promoted ? 'text-gray-100' : 'text-gray-800 dark:text-dark-400'">{{ feature }}</span>
                            </div>
                            <div v-else class="flex items-center text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>

                                <span class="mx-4">{{ feature }}</span>
                            </div>
                        </div>
                    </div>

                    <Link v-if="$page.props.canRegister" :href="route('register')" class="block text-center w-full px-4 py-2 mt-10 font-medium tracking-wide text-white capitalize transition-colors duration-300 transform bg-indigo-500 rounded-md hover:bg-indigo-600 focus:outline-none focus:bg-indigo-600">
                        Get started
                    </Link>
                </div>
            </div>
            <div v-if="plans.filter((plan) => plan.tax_mode_monthly === 'external' || plan.tax_mode_yearly === 'external').length"
                 class="mt-10 text-center text-gray-400 px-4 sm:pm-0">
                * All prices are net prices. Local taxes and VAT may be added in the checkout. You can remove VAT by adding your VAT id.
            </div>
            <div class="mt-4 text-base text-center text-gray-600 dark:text-dark-400 px-4 sm:pm-0">
                If these plans do not fit your needs, feel free to <a class="underline" href="mailto:simplestats@laracraft.tech">contact sales</a>.
            </div>
            <div class="mt-4 text-base text-center text-gray-600 dark:text-dark-400 px-4 sm:pm-0">
                Wonder what an <a class="underline" href="#what-is-an-event">event</a> is?
            </div>
        </div>
    </section>
</template>
