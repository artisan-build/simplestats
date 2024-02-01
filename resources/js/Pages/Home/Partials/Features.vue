<script setup>
import {
    ChartBarIcon,
    CodeBracketIcon,
    GiftIcon,
    LightBulbIcon, MapPinIcon,
    RocketLaunchIcon,
    ShieldCheckIcon,
    UserGroupIcon
} from "@heroicons/vue/24/outline/index.js";

import LaravelIcon from "@/Components/CustomIcons/LaravelIcon.vue";

const features = [
    {
        "icon": LightBulbIcon,
        "title": "Different",
        "descr": "Most analytic tools show you data like visits, views and page impressions. Everything beyond that, you need to integrate manually. That's where SimpleStats shines, it gives you super-powers without the integration-hustle."
    },
    {
        "icon": RocketLaunchIcon,
        "title": "Simple",
        "descr": "All you need to do to get SimpleStats running: Install a composer package, adjust the config to your needs, create an account and add the token to your env. Congrats you're ready to analyze your campaigns and users!"
    },
    {
        "icon": LaravelIcon,
        "title": "Laravel",
        "descr": "Since the statistic tool and the composer client package is tailored precisely to Laravel, it can collect very interesting and important data without any additional complexity for you as the integrator."
    },
    {
        "icon": CodeBracketIcon,
        "title": "API",
        "descr": "Even though the composer client package is dedicated to Laravel, the API basically works with every client! So if you like to use to tool, even if you're not using Laravel, just trigger the API requests manually."
    },
    {
        "icon": MapPinIcon,
        "title": "UTMs and Referrers",
        "descr": "Since the tool by default collects UTMs and Referrers for you and connects it with your users and their payments, it's super easy to see which of your marketing activities leads to revenue. You can simply analyse the ROI of your campaigns in the dashboard!"
    },
    {
        "icon": ChartBarIcon,
        "title": "KPIs",
        "descr": "Here are a few of the KPIs that the tool makes available to you: Registrations, Daily Active Users, New Active Paying Users, Average Revenue per User, Paying User, Revenue and much more. Everything filterable by date and UTMs!"
    },
    {
        "icon": ShieldCheckIcon,
        "title": "Privacy",
        "descr": "We're fully privacy compliant! No cookies are needed or stored. You can trust SimpleStats, it respects your privacy and will never share your data."
    },
    {
        "icon": UserGroupIcon,
        "title": "Collaborate",
        "descr": "Create a team, invite your co-workers and assign permissions to them. Each team is tenancy-separated and therefore will ensure a very secure and robust data integration!"
    },
    {
        "icon": GiftIcon,
        "title": "Free",
        "descr": "There will always be a free plan! If your business grows, you can support us with a subscription. We would love to help you analyzing your campaigns. No need to enter credit card information at the registration."
    }
];
</script>

<template>
    <div class="max-w-screen-xl lg:flex lg:flex-wrap lg:justify-around xl:mt-6 xl:px-8">
        <div v-for="feature in features" class="mt-10 flex w-full max-w-md lg:w-1/3 lg:flex-col lg:p-5">
            <div class="mr-4 flex items-center gap-x-5 lg:mr-0 text-indigo-600">
                <component class="h-10 w-10" :is="feature.icon" />
                <h4 class="text-sm font-semibold text-gray-800 lg:text-lg xl:text-xl">
                    {{ feature.title }}
                </h4>
            </div>
            <div class="lg:mt-5">
                <p class="mt-2 text-xs leading-normal text-gray-600 lg:text-sm xl:text-base">
                    {{ feature.descr }}
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
