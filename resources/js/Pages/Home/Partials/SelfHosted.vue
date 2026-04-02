<script setup>
import SectionHeader from "@/Pages/Home/Partials/SectionHeader.vue";

const props = defineProps({
    discount: Number,
})

import { trans } from 'laravel-vue-i18n';

const appName = import.meta.env.VITE_APP_NAME;

const getPlanPrice = (amount, discount) => {
    amount = amount  / 100;

    if (discount) {
        amount = Math.floor(amount * (discount / 100));
    }

    return '$' + amount;
}

const licenses = [
    {
        "name": () => trans("Single domain"),
        "short_description": () => trans("For one domain or subdomain. Powerful analytics with package and API. One year of updates, no subscription."),
        "price": 24900,
        "promoted": false,
        "features": [
            () => trans("One domain or subdomain"),
            () => trans("Package and API included"),
            () => trans("Robust analytics features"),
            () => trans("Great for running SimpleStats standalone"),
            () => trans("1 year of free updates, no subscription"),
        ]
    },
    {
        "name": () => trans("Unlimited domains"),
        "short_description": () => trans("No domain limits. Integrate into products. Full analytics suite. One year of updates, no subscription."),
        "price": 99900,
        "promoted": true,
        "features": [
            () => trans("No domain restrictions"),
            () => trans("Package and API included"),
            () => trans("Robust analytics features"),
            () => trans("May be integrated into products"),
            () => trans("1 year of free updates, no subscription"),
        ]
    }
];

const mailSubject = "New Self-Hosted Customer";
const mailBody = `Thank you for your interest in the self-hosted version of SimpleStats.io!

Once you've completed the questions below and sent us the email, we'll issue an invoice.
After we receive your payment, we'll grant your GitHub account access to the SimpleStats repository, which includes the installation instructions.

---------------

Please answer the following questions:

Which license type would you like to purchase (Single Domain or Unlimited Domains)?
YOUR ANSWER HERE

What is your billing address?
YOUR ANSWER HERE

Do you have a VAT ID (optional)?
YOUR ANSWER HERE

What is your GitHub username?
YOUR ANSWER HERE`;
</script>

<template>
    <section class="flex justify-center">
        <div class="max-w-screen-lg px-4 sm:pm-0">
            <SectionHeader id="self-hosted">
                {{ $t('Take full control with') }}&nbsp;<span class="text-indigo-500">{{ $t('Self-Hosted') }}</span>
            </SectionHeader>

            <!-- the JIT Compiler from tailwindcss cant recognize things like this "md:grid-cols-'+licenses.length/2+'" so we need to put the classes here manually so that the exists --->
            <div class="hidden md:grid-cols-2 md:grid-cols-3 md:grid-cols-4 lg:grid-cols-2 lg:grid-cols-3 lg:grid-cols-4"></div>
            <div class="grid gap-x-8  grid gap-y-10 lg:gap-y-20 px-6" :class="'md:grid-cols-'+licenses.length/2+' lg:grid-cols-'+licenses.length">
                <div v-for="license in licenses"
                     class="px-6 py-6 transition-colors duration-300 transform rounded-lg hover:bg-gray-100 dark:hover:bg-dark-800"
                     :class="license.promoted ? 'bg-gray-800 hover:bg-gray-800 dark:bg-dark-800' : ''"
                >
                    <div class="flex justify-between flex-wrap text-2xl font-medium dark:text-dark-100" :class="license.promoted ? 'text-gray-100' : 'text-gray-800'">
                        <div>{{ license.name() }}</div>
                    </div>

                    <div class="mt-2 text-3xl font-semibold" :class="license.promoted ? 'text-gray-100' : 'text-gray-800 dark:text-dark-200'">
                        <span v-if="discount">
                            <span class="line-through text-base">
                                {{ getPlanPrice(license.price, 0) }}
                            </span>
                            {{ getPlanPrice(license.price, discount) }}
                        </span>
                        <span v-else>
                            {{ getPlanPrice(license.price, 0) }}
                        </span>
                    </div>

                    <p class="mt-4" :class="license.promoted ? 'text-gray-100' : 'text-gray-800 dark:text-dark-400'">
                        {{ license.short_description() }}
                    </p>

                    <div class="mt-8 space-y-8">
                        <div v-for="(feature, option) in license.features">
                            <div class="flex items-center" :class="license.promoted ? 'text-gray-100' : 'text-gray-800 dark:text-dark-400'">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-indigo-500">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>

                                <span class="mx-4" :class="license.promoted ? 'text-gray-100' : 'text-gray-800 dark:text-dark-400'">{{ feature() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-10 m-auto px-32">
                <p class="leading-normal text-gray-600 text-lg hidden">
                    Embrace the ultimate level of autonomy and security with our Self-Hosted solution.
                    Ideal for those who demand full control.
                    This option allows you to host our powerful analytics tool on your own infrastructure,
                    ensuring complete data sovereignty and customizable security.
                </p>

                <p class="mt-10 leading-normal text-gray-600 dark:text-dark-300 text-lg">
                    {{ $t('Start') }} <a class="simpleLink italic" href="/docs/self-hosted.html" target="_blank">{{ appName }} {{ $t('Self-Hosted') }}</a> {{ $t('today and take full control!') }}<br>
                </p>

                <div class="md:inline-flex mt-10">
                    <a :href="`mailto:simplestats@laracraft.tech?subject=${encodeURIComponent(mailSubject)}&body=${encodeURIComponent(mailBody)}`" class="whitespace-nowrap block text-center px-4 py-2 font-medium tracking-wide text-white capitalize transition-colors duration-300 transform bg-indigo-500 rounded-md hover:bg-indigo-600 focus:outline-none focus:bg-indigo-600">
                        {{ $t('Contact Sales') }}
                    </a>
                    &nbsp;&nbsp;&nbsp;
                    <a v-if="$page.props.canRegister" href="https://calendly.com/simplestats/30min" target="_blank" class="whitespace-nowrap block text-center px-4 py-2 font-medium tracking-wide text-white capitalize transition-colors duration-300 transform bg-indigo-500 rounded-md hover:bg-indigo-600 focus:outline-none focus:bg-indigo-600">
                        {{ $t('Book a call') }}
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>
