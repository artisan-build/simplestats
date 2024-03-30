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
import SectionHeader from "@/Pages/Home/Partials/SectionHeader.vue";

const features = [
    {
        "icon": LightBulbIcon,
        "title": "Different",
        "descr": "Most analytic tools show you data like visits, views, and page impressions. Everything beyond that, you need to integrate manually. That's where SimpleStats shines; it gives you superpowers without the integration hustle."
    },
    {
        "icon": RocketLaunchIcon,
        "title": "Simple",
        "descr": "All you need to do to get SimpleStats up and running is: Install a composer package, adjust the config to your needs, create an account, and add the token to your env file. Congrats, you're ready to analyze your campaigns and users!"
    },
    {
        "icon": LaravelIcon,
        "title": "Laravel",
        "descr": "Since the statistics tool and the composer client package are tailored precisely to Laravel, they can collect very interesting and important data without any additional complexity for you as the integrator of the package."
    },
    {
        "icon": CodeBracketIcon,
        "title": "API",
        "descr": "Even though the composer client package is dedicated to Laravel, the API basically works with every client! So if you'd like to use the tool, even if you're not using Laravel, just trigger the API requests manually."
    },
    {
        "icon": MapPinIcon,
        "title": "UTMs and Referrers",
        "descr": "Since the tool by default collects UTMs and Referrers for you and connects it with your users and their payments, it lets you seamlessly check the ROI of each of your campaigns and allows you to see where you can buy the cheapest users and from which channel users perform best!"
    },
    {
        "icon": ChartBarIcon,
        "title": "KPIs",
        "descr": "Here are a few of the KPIs that the tool provides: Registrations, Daily Active Users, New Active Paying Users, Average Revenue per User, Paying User, Revenue and much more. Everything filterable by date and UTMs!"
    },
    {
        "icon": ShieldCheckIcon,
        "title": "Privacy",
        "descr": "We are fully committed to privacy compliance! No cookies are required or stored, ensuring your data remains confidential. You can rely on SimpleStats to respect your privacy and guarantee that your data will never be shared."
    },
    {
        "icon": UserGroupIcon,
        "title": "Collaborate",
        "descr": "Collaborate by creating a team, inviting your co-workers to your projects, and assigning permissions to them. Each team is separated by tenancy, ensuring highly secure, robust and solid data integration!"
    },
    {
        "icon": GiftIcon,
        "title": "Free",
        "descr": "There will always be a free plan! If your business grows, you can support us with by subscribing. We would love to help you analyzing your campaigns. No need to enter credit card information at the registration."
    }
];
</script>

<template>
    <section class="flex justify-center">
        <div class="max-w-screen-md lg:max-w-screen-xl">
            <SectionHeader id="features">
                What makes us&nbsp;<span class="text-indigo-500">unique</span>
            </SectionHeader>
             <div class="grid gap-x-14  grid gap-y-10 lg:gap-y-20 lg:grid-cols-3 px-6">
                <div v-for="feature in features">
                    <div class="mr-4 flex items-center gap-x-5 lg:mr-0 text-indigo-600">
                        <component class="h-10 w-10" :is="feature.icon" />
                        <h4 class="text-sm font-semibold text-gray-800 lg:text-lg xl:text-xl">
                            {{ feature.title }}
                        </h4>
                    </div>
                    <div class="lg:mt-5">
                        <p class="mt-2 leading-normal text-gray-600 text-base">
                            {{ feature.descr }}
                        </p>
                    </div>
                </div>
             </div>
        </div>
    </section>
</template>

<style scoped>

</style>
