<script setup>
import {
    ChartBarIcon,
    CodeBracketIcon,
    GiftIcon,
    LightBulbIcon,
    MapPinIcon,
    PresentationChartBarIcon,
    RocketLaunchIcon,
    ShieldCheckIcon,
    UserGroupIcon
} from "@heroicons/vue/24/outline/index.js";

import SectionHeader from "@/Pages/Home/Partials/SectionHeader.vue";

import {trans} from 'laravel-vue-i18n';

const f = {
    different: {
        icon: LightBulbIcon,
        title: () => trans("Different"),
        descr: () => trans("Traditional client-side analytic tools are only about 70% accurate because of ad blockers and they often only show you data like visits and views. That's where server-side analytics like SimpleStats shines; it gives you superpowers without the integration hustle!"),
    },
    simple: {
        icon: RocketLaunchIcon,
        title: () => trans("Simple"),
        descr: () => trans("All you need to do to get SimpleStats up and running is: Install a composer package, adjust the config to your needs, create an account, and add the token to your env file. Congrats, you're ready to analyze your campaigns and users!"),
    },
    reporting: {
        icon: PresentationChartBarIcon,
        title: () => trans("Reporting"),
        descr: () => trans("The tool provides you with a powerful reporting system that allows you to create custom reports, filter them by location, device, and UTM parameters. The frequency of the reports can be saved and scheduled individually, so you never miss important updates!"),
    },
    api: {
        icon: CodeBracketIcon,
        title: () => trans("API"),
        descr: () => trans("Even though the composer client package is dedicated to Laravel, the API basically works with every client! So if you'd like to use the tool, even if you're not using Laravel, just trigger the API requests manually and you'll be able to use the tool for any custom project."),
    },
    utms: {
        icon: MapPinIcon,
        title: () => trans("UTMs and Referrers"),
        descr: () => trans("Since the tool by default collects UTMs and Referrers for you and connects it with your users and their payments, it lets you seamlessly check the ROI of each of your campaigns and allows you to see where you can buy the cheapest users and from which channel users perform best!"),
    },
    kpis: {
        icon: ChartBarIcon,
        title: () => trans("KPIs"),
        descr: () => trans("Here are a few of the KPIs that the tool provides out of the box: Unique Visitors, Registrations, Conversion Rate, Daily Active Users, New Active Paying Users, Average Revenue per User, Revenue and much more! Everything filterable by date, locations, devices and UTMs."),
    },
    privacy: {
        icon: ShieldCheckIcon,
        title: () => trans("Privacy"),
        descr: () => trans("We are fully committed to GDPR compliance! No cookies are required or stored, ensuring your data remains confidential. You can rely on SimpleStats to respect your privacy and guarantee that your data will never be shared."),
    },
    teams: {
        icon: UserGroupIcon,
        title: () => trans("Collaborate"),
        descr: () => trans("Collaborate by creating a team, inviting your co-workers to your projects, and assigning permissions to them. Each team is separated by tenancy, ensuring highly secure, robust and solid data integration!"),
    },
    free: {
        icon: GiftIcon,
        title: () => trans("Free"),
        descr: () => trans("There will always be a free plan! If your business grows, you can support us with by subscribing. We would love to help you analyzing your campaigns. No need to enter credit card information at the registration."),
    },
};
</script>

<template>
    <section id="features" class="flex justify-center">
        <div class="w-full max-w-screen-xl px-6">
            <SectionHeader>
                {{ $t('What makes us') }}&nbsp;<span class="text-indigo-500 underline">{{ $t('unique') }}</span>
            </SectionHeader>
            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 lg:gap-6">
                <!-- Hero card: Different -->
                <div class="relative overflow-hidden lg:col-span-3 lg:row-span-2 rounded-3xl bg-white dark:bg-dark-800 p-8 lg:p-12 ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="absolute -top-32 -right-32 size-96 rounded-full bg-indigo-500/15 blur-3xl pointer-events-none"></div>
                    <div class="relative flex flex-col gap-6">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center size-12 rounded-xl bg-indigo-600 text-white">
                                <component :is="f.different.icon" class="size-6" />
                            </div>
                            <span class="text-xs font-semibold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">{{ $t('Server-side') }}</span>
                        </div>
                        <dt class="text-3xl lg:text-4xl font-bold tracking-tight text-gray-900 dark:text-white max-w-xl">
                            {{ f.different.title() }}
                        </dt>
                        <div class="flex items-end gap-8 tabular-nums">
                            <div>
                                <div class="text-5xl lg:text-6xl font-extrabold text-indigo-600 dark:text-indigo-400">100%</div>
                                <div class="mt-1 text-sm font-medium text-gray-600 dark:text-dark-400">{{ $t('accurate') }}</div>
                            </div>
                            <div class="h-16 w-px bg-gray-950/10 dark:bg-white/10"></div>
                            <div>
                                <div class="text-5xl lg:text-6xl font-extrabold text-gray-300 dark:text-dark-600 line-through decoration-2">70%</div>
                                <div class="mt-1 text-sm font-medium text-gray-500 dark:text-dark-500">{{ $t('client-side') }}</div>
                            </div>
                        </div>
                        <dd class="text-base lg:text-lg leading-relaxed text-gray-600 dark:text-dark-400 max-w-2xl">
                            {{ f.different.descr() }}
                        </dd>
                    </div>
                </div>

                <!-- Tall card: Privacy -->
                <div class="lg:row-span-2 flex flex-col gap-4 rounded-3xl bg-indigo-600 p-8 text-white shadow-lg shadow-indigo-600/20">
                    <div class="flex items-center justify-center size-12 rounded-xl bg-white/15 backdrop-blur">
                        <component :is="f.privacy.icon" class="size-6" />
                    </div>
                    <dt class="text-2xl font-bold tracking-tight">{{ f.privacy.title() }}</dt>
                    <div class="text-sm font-semibold uppercase tracking-widest text-indigo-200">{{ $t('GDPR compliant') }}</div>
                    <dd class="text-base leading-relaxed text-indigo-100">{{ f.privacy.descr() }}</dd>
                </div>

                <!-- Row: Reporting wide + UTMs -->
                <div class="lg:col-span-2 flex flex-col gap-3 rounded-3xl bg-white dark:bg-dark-800 p-7 ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="flex items-center justify-center size-11 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                        <component :is="f.reporting.icon" class="size-6" />
                    </div>
                    <dt class="text-xl font-semibold text-gray-900 dark:text-white">{{ f.reporting.title() }}</dt>
                    <dd class="text-sm leading-relaxed text-gray-600 dark:text-dark-400">{{ f.reporting.descr() }}</dd>
                </div>
                <div class="flex flex-col gap-3 rounded-3xl bg-white dark:bg-dark-800 p-7 ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="flex items-center justify-center size-11 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                        <component :is="f.utms.icon" class="size-6" />
                    </div>
                    <dt class="text-lg font-semibold text-gray-900 dark:text-white">{{ f.utms.title() }}</dt>
                    <dd class="text-sm leading-relaxed text-gray-600 dark:text-dark-400 line-clamp-3">{{ f.utms.descr() }}</dd>
                </div>

                <!-- Bottom row: 5 small cards -->
                <div class="flex flex-col gap-3 rounded-3xl bg-white dark:bg-dark-800 p-7 ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="flex items-center justify-center size-11 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                        <component :is="f.kpis.icon" class="size-6" />
                    </div>
                    <dt class="text-lg font-semibold text-gray-900 dark:text-white">{{ f.kpis.title() }}</dt>
                    <dd class="text-sm leading-relaxed text-gray-600 dark:text-dark-400 line-clamp-3">{{ f.kpis.descr() }}</dd>
                </div>
                <div class="flex flex-col gap-3 rounded-3xl bg-white dark:bg-dark-800 p-7 ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="flex items-center justify-center size-11 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                        <component :is="f.simple.icon" class="size-6" />
                    </div>
                    <dt class="text-lg font-semibold text-gray-900 dark:text-white">{{ f.simple.title() }}</dt>
                    <dd class="text-sm leading-relaxed text-gray-600 dark:text-dark-400 line-clamp-3">{{ f.simple.descr() }}</dd>
                </div>
                <div class="flex flex-col gap-3 rounded-3xl bg-white dark:bg-dark-800 p-7 ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="flex items-center justify-center size-11 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                        <component :is="f.api.icon" class="size-6" />
                    </div>
                    <dt class="text-lg font-semibold text-gray-900 dark:text-white">{{ f.api.title() }}</dt>
                    <dd class="text-sm leading-relaxed text-gray-600 dark:text-dark-400 line-clamp-3">{{ f.api.descr() }}</dd>
                </div>
                <div class="flex flex-col gap-3 rounded-3xl bg-white dark:bg-dark-800 p-7 ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="flex items-center justify-center size-11 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                        <component :is="f.teams.icon" class="size-6" />
                    </div>
                    <dt class="text-lg font-semibold text-gray-900 dark:text-white">{{ f.teams.title() }}</dt>
                    <dd class="text-sm leading-relaxed text-gray-600 dark:text-dark-400 line-clamp-3">{{ f.teams.descr() }}</dd>
                </div>
                <div class="flex flex-col gap-3 rounded-3xl bg-white dark:bg-dark-800 p-7 ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="flex items-center justify-center size-11 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                        <component :is="f.free.icon" class="size-6" />
                    </div>
                    <dt class="text-lg font-semibold text-gray-900 dark:text-white">{{ f.free.title() }}</dt>
                    <dd class="text-sm leading-relaxed text-gray-600 dark:text-dark-400 line-clamp-3">{{ f.free.descr() }}</dd>
                </div>
            </dl>
        </div>
    </section>
</template>
