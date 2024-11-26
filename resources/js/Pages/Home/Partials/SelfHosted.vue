<script setup>
import SectionHeader from "@/Pages/Home/Partials/SectionHeader.vue";
import {Link} from "@inertiajs/vue3";

const props = defineProps({
    discount: Number,
})

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
        "name": "Single domain",
        "short_description": "For one domain or subdomain. Powerful analytics with package and API. One year of updates, no subscription.",
        "price": 24900,
        "promoted": false,
        "features": [
            "One domain or subdomain",
            "Package and API included",
            "Robust analytics features",
            "Great for running SimpleStats standalone",
            "1 year of free updates, no subscription",
        ]
    },
    {
        "name": "Unlimited domains",
        "short_description": "No domain limits. Integrate into products. Full analytics suite. One year of updates, no subscription.",
        "price": 99900,
        "promoted": true,
        "features": [
            "No domain restrictions",
            "Package and API included",
            "Robust analytics features",
            "May be integrated into products",
            "1 year of free updates, no subscription",
        ]
    }
];
</script>

<template>
    <section class="pb-32 flex justify-center">
        <div class="max-w-screen-lg px-4 sm:pm-0">
            <SectionHeader id="self-hosted">
                Take full control with&nbsp;<span class="text-indigo-500">Self-Hosted</span>
            </SectionHeader>

            <!-- the JIT Compiler from tailwindcss cant recognize things like this "md:grid-cols-'+licenses.length/2+'" so we need to put the classes here manually so that the exists --->
            <div class="hidden md:grid-cols-2 md:grid-cols-3 md:grid-cols-4 lg:grid-cols-2 lg:grid-cols-3 lg:grid-cols-4"></div>
            <div class="grid gap-x-8  grid gap-y-10 lg:gap-y-20 px-6" :class="'md:grid-cols-'+licenses.length/2+' lg:grid-cols-'+licenses.length">
                <div v-for="license in licenses"
                     class="px-6 py-6 transition-colors duration-300 transform rounded-lg dark:hover:bg-gray-800"
                     :class="license.promoted ? 'bg-gray-700' : 'hover:bg-gray-200'"
                >
                    <div class="flex justify-between flex-wrap text-2xl font-medium dark:text-gray-100" :class="license.promoted ? 'text-gray-100' : 'text-gray-800'">
                        <div>{{ license.name }}</div>
                    </div>

                    <h4 class="mt-2 text-3xl font-semibold" :class="license.promoted ? 'text-gray-100' : 'text-gray-800'">
                        <span v-if="discount">
                            <span class="line-through text-base">
                                {{ getPlanPrice(license.price, 0) }}
                            </span>
                            {{ getPlanPrice(license.price, discount) }}
                        </span>
                        <span v-else>
                            {{ getPlanPrice(license.price, 0) }}
                        </span>
                    </h4>

                    <p class="mt-4" :class="license.promoted ? 'text-gray-100' : 'text-gray-800'">
                        {{ license.short_description }}
                    </p>

                    <div class="mt-8 space-y-8">
                        <div v-for="(feature, option) in license.features">
                            <div class="flex items-center" :class="license.promoted ? 'text-gray-100' : 'text-gray-800'">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 text-indigo-500">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>

                                <span class="mx-4" :class="license.promoted ? 'text-gray-100' : 'text-gray-800'">{{ feature }}</span>
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

                <p class="mt-10 leading-normal text-gray-600 text-lg">
                    Start <span class="italic">{{ appName }} Self-Hosted</span> today and take full control!
                </p>

                <div class="md:inline-flex mt-10">
                    <a href="mailto:simplestats@laracraft.tech" class="whitespace-nowrap block text-center px-4 py-2 font-medium tracking-wide text-white capitalize transition-colors duration-300 transform bg-indigo-500 rounded-md hover:bg-indigo-600 focus:outline-none focus:bg-indigo-600">
                        Contact Sales
                    </a>
                    &nbsp;&nbsp;&nbsp;
                    <a v-if="$page.props.canRegister" href="https://calendly.com/simplestats/30min" target="_blank" class="whitespace-nowrap block text-center px-4 py-2 font-medium tracking-wide text-white capitalize transition-colors duration-300 transform bg-indigo-500 rounded-md hover:bg-indigo-600 focus:outline-none focus:bg-indigo-600">
                        Book a call
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>
