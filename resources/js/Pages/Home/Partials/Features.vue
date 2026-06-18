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
        descr: () => trans("Install the Composer package, add your token to the .env file, and you're ready to analyze your campaigns and users."),
    },
    reporting: {
        icon: PresentationChartBarIcon,
        title: () => trans("Reporting"),
        descr: () => trans("The tool provides you with a powerful reporting system that allows you to create custom reports, filter them by location, device, and UTM parameters. The frequency of the reports can be saved and scheduled individually, so you never miss important updates!"),
    },
    api: {
        icon: CodeBracketIcon,
        title: () => trans("API"),
        descr: () => trans("The REST API works with any stack, not just Laravel. Trigger the requests manually and track any custom project."),
    },
    utms: {
        icon: MapPinIcon,
        title: () => trans("UTMs and Referrers"),
        descr: () => trans("UTMs and Referrers are collected automatically and linked to your users and payments, so you see the real ROI and best-performing channel of every campaign."),
    },
    kpis: {
        icon: ChartBarIcon,
        title: () => trans("KPIs"),
        descr: () => trans("Unique Visitors, Conversion Rate, Daily Active Users, ARPU, Revenue and many more KPIs out of the box, all filterable by date, location, device and UTM."),
    },
    privacy: {
        icon: ShieldCheckIcon,
        title: () => trans("Privacy"),
        descr: () => trans("We are fully committed to GDPR compliance! No cookies are required or stored, ensuring your data remains confidential. You can rely on SimpleStats to respect your privacy and guarantee that your data will never be shared."),
    },
    teams: {
        icon: UserGroupIcon,
        title: () => trans("Collaborate"),
        descr: () => trans("Create a team, invite co-workers to your projects, and assign permissions. Each team is fully separated by tenancy."),
    },
    free: {
        icon: GiftIcon,
        title: () => trans("Free"),
        descr: () => trans("There will always be a free plan, with no credit card required to sign up. Subscribe to support us as your business grows."),
    },
};
</script>

<template>
    <section class="flex justify-center">
        <div class="w-full max-w-screen-xl px-6">
            <SectionHeader id="features" :eyebrow="$t('Features')">
                {{ $t('What makes us') }}&nbsp;<span class="text-indigo-500 underline">{{ $t('unique') }}</span>
            </SectionHeader>
            <ul role="list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 lg:gap-6">
                <!-- Hero card: Different -->
                <li class="relative overflow-hidden lg:col-span-3 lg:row-span-2 rounded-3xl bg-white dark:bg-dark-800 p-8 lg:p-12 ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="absolute -top-32 -right-32 size-96 rounded-full bg-indigo-500/15 blur-3xl pointer-events-none"></div>
                    <div class="relative flex flex-col gap-6">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center size-12 rounded-xl bg-indigo-600 text-white">
                                <component :is="f.different.icon" class="size-6" />
                            </div>
                            <span class="text-xs font-semibold uppercase tracking-widest text-indigo-600 dark:text-indigo-400">{{ $t('Server-side') }}</span>
                        </div>
                        <h3 class="text-3xl lg:text-4xl font-bold tracking-tight text-gray-900 dark:text-white max-w-xl">
                            {{ f.different.title() }}
                        </h3>
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
                        <p class="text-base lg:text-lg leading-relaxed text-gray-600 dark:text-dark-400 max-w-2xl">
                            {{ f.different.descr() }}
                        </p>
                    </div>
                </li>

                <!-- Tall card: Privacy -->
                <li class="lg:row-span-2 flex flex-col gap-4 rounded-3xl bg-indigo-600 p-8 text-white shadow-lg shadow-indigo-600/20">
                    <div class="flex items-center justify-center size-12 rounded-xl bg-white/15 backdrop-blur">
                        <component :is="f.privacy.icon" class="size-6" />
                    </div>
                    <h3 class="text-2xl font-bold tracking-tight">{{ f.privacy.title() }}</h3>
                    <div class="text-sm font-semibold uppercase tracking-widest text-indigo-200">{{ $t('GDPR compliant') }}</div>
                    <p class="text-base leading-relaxed text-indigo-100">{{ f.privacy.descr() }}</p>
                </li>

                <!-- Row: Reporting wide + UTMs -->
                <li class="lg:col-span-2 flex flex-col gap-3 rounded-3xl bg-white dark:bg-dark-800 p-7 ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="flex items-center justify-center size-11 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                        <component :is="f.reporting.icon" class="size-6" />
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">{{ f.reporting.title() }}</h3>
                    <p class="text-sm leading-relaxed text-gray-600 dark:text-dark-400">{{ f.reporting.descr() }}</p>
                </li>
                <li class="flex flex-col gap-3 rounded-3xl bg-white dark:bg-dark-800 p-7 ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="flex items-center justify-center size-11 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                        <component :is="f.utms.icon" class="size-6" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ f.utms.title() }}</h3>
                    <p class="text-sm leading-relaxed text-gray-600 dark:text-dark-400">{{ f.utms.descr() }}</p>
                </li>

                <!-- Bottom row: 5 small cards -->
                <li class="flex flex-col gap-3 rounded-3xl bg-white dark:bg-dark-800 p-7 ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="flex items-center justify-center size-11 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                        <component :is="f.kpis.icon" class="size-6" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ f.kpis.title() }}</h3>
                    <p class="text-sm leading-relaxed text-gray-600 dark:text-dark-400">{{ f.kpis.descr() }}</p>
                </li>
                <li class="flex flex-col gap-3 rounded-3xl bg-white dark:bg-dark-800 p-7 ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="flex items-center justify-center size-11 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                        <component :is="f.simple.icon" class="size-6" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ f.simple.title() }}</h3>
                    <p class="text-sm leading-relaxed text-gray-600 dark:text-dark-400">{{ f.simple.descr() }}</p>
                </li>
                <li class="flex flex-col gap-3 rounded-3xl bg-white dark:bg-dark-800 p-7 ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="flex items-center justify-center size-11 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                        <component :is="f.api.icon" class="size-6" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ f.api.title() }}</h3>
                    <p class="text-sm leading-relaxed text-gray-600 dark:text-dark-400">{{ f.api.descr() }}</p>
                </li>
                <li class="flex flex-col gap-3 rounded-3xl bg-white dark:bg-dark-800 p-7 ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="flex items-center justify-center size-11 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                        <component :is="f.teams.icon" class="size-6" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ f.teams.title() }}</h3>
                    <p class="text-sm leading-relaxed text-gray-600 dark:text-dark-400">{{ f.teams.descr() }}</p>
                </li>
                <li class="flex flex-col gap-3 rounded-3xl bg-white dark:bg-dark-800 p-7 ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="flex items-center justify-center size-11 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                        <component :is="f.free.icon" class="size-6" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ f.free.title() }}</h3>
                    <p class="text-sm leading-relaxed text-gray-600 dark:text-dark-400">{{ f.free.descr() }}</p>
                </li>
            </ul>
        </div>
    </section>
</template>
