<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCols from "@/Pages/Dashboard/Partials/StatsCols.vue";
import StatsFilter from "@/Pages/Dashboard/Partials/StatsFilter.vue";
import StatsCharts from "@/Pages/Dashboard/Partials/StatsCharts.vue";
import StatsGroups from "@/Pages/Dashboard/Partials/StatsGroups.vue";
import {ARPXAU, XAAPU, XAPU, XAU} from "@/Helpers/StringHelpers.js";
import FreePlanWarning from "@/Pages/Dashboard/Partials/FreePlanWarning.vue";
import SubscriptionPlanAlarm from "@/Pages/Dashboard/Partials/SubscriptionPlanAlarm.vue";
import {onMounted, onUnmounted, ref} from "vue";

defineProps({
    teamStats: Object,
    filters: Object,
    statsGroups: Object,
    statsGroupNames: Object,
    topReferers: Object,
    rangeTypes: Array,
    freePlanWarning: Object,
    subscriptionPlanAlarm: Array,
});

const isScrolled = ref(false)

const handleScroll = () => {
    isScrolled.value = window.scrollY > 0
}

onMounted(() => {
    window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll)
})
</script>

<template>
    <AppLayout title="Dashboard" :fullscreen="true">
        <template #header>Dashboard</template>
        <div v-if="freePlanWarning.show">
            <FreePlanWarning :freePlanWarning="freePlanWarning" />
        </div>
        <div v-if="subscriptionPlanAlarm.length > 0" class="mb-6">
            <SubscriptionPlanAlarm :subscriptionPlanAlarm="subscriptionPlanAlarm" />
        </div>
        <div>
            <div class="sticky top-0 px-8 py-4 -mx-8 -my-4 mb-4 bg-gray-50 z-20" :class="{'border-b shadow-sm': isScrolled}">
                <StatsFilter
                    :teamStats="teamStats"
                    :statsGroups="statsGroups"
                    :statsGroupNames="statsGroupNames"
                    :filters="filters"
                    :rangeTypes="rangeTypes"
                    :topReferers="topReferers"
                />
            </div>
            <div class="grid gap-8 grid-cols-1" :class="filters.projectId ? 'lg:grid-cols-2 2xl:grid-cols-3' : 'lg:grid-cols-1 2xl:grid-cols-2'">
                <div class="2xl:col-span-2">
                    <StatsCharts
                        :rangeType="filters.rangeType"
                        :teamStats="teamStats"
                    />
                </div>
                <div v-if="filters.projectId">
                    <StatsGroups
                        :filters="filters"
                        :statsGroups="statsGroups"
                        :topReferers="topReferers"
                    />
                </div>
            </div>
            <div v-if="0" class="bg-white dark:bg-gray-800 rounded-md shadow overflow-x-auto mb-8 border p-1">
                <table class="w-full whitespace-nowrap text-sm">
                    <thead>
                        <tr class="text-left font-bold text-base">
                            <th class="p-3">&nbsp;</th>
                            <th class="w-1"></th>
                            <th class="text-center" colspan="6">User</th>
                            <th class="w-1"></th>
                            <th class="text-center" colspan="4">Revenue</th>
                            <th class="w-1"></th>
                            <th class="text-center" colspan="9">Lifetime</th>
                        </tr>
                        <tr class="text-center font-bold text-gray-600 bg-gray-100">
                            <th class="border-r border-gray-200 p-3 text-left">Date</th>
                            <th class="w-1 bg-white"></th>
                            <th class="border-r border-gray-200 pr-1">Regs</th>
                            <th class="border-r border-gray-200 pr-1">NAU</th>
                            <th class="border-r border-gray-200 pr-1">NAPU</th>
                            <th class="border-r border-gray-200 pr-1">{{ XAU(filters.rangeType) }}</th>
                            <th class="border-r border-gray-200 pr-1">{{ XAPU(filters.rangeType) }}</th>
                            <th class="border-r border-gray-200 pr-1">{{ XAAPU(filters.rangeType) }}</th>
                            <th class="w-1 bg-white"></th>
                            <th class="border-r border-gray-200 pr-1">Gross</th>
                            <th class="border-r border-gray-200 pr-1">Net</th>
                            <th class="border-r border-gray-200 pr-1">Fee</th>
                            <th class="border-r border-gray-200 pr-1">{{ ARPXAU(filters.rangeType) }}</th>
                            <th class="w-1 bg-white"></th>
                            <th class="border-r border-gray-200 pr-1">Regs</th>
                            <th class="border-r border-gray-200 pr-1">Reg. to {{ XAU(filters.rangeType) }}</th>
                            <th class="border-r border-gray-200 pr-1">Pay User</th>
                            <th class="border-r border-gray-200 pr-1">Reg. to Pay</th>
                            <th class="border-r border-gray-200 pr-1">Tot. Actives</th>
                            <th class="border-r border-gray-200 pr-1">Reg. to Active</th>
                            <th class="border-r border-gray-200 pr-1">ARPU</th>
                            <th class="border-r border-gray-200 pr-1">ARPPU</th>
                            <th class="pr-2">Rev. (N)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="teamStats.stats" v-for="(stats, i) in teamStats.stats" :key="stats.date"
                            class="hover:bg-gray-100 focus-within:bg-gray-50 text-gray-700 text-right"
                            :class="{'bg-gray-50': i % 2 === 1, 'border-t': i === 'total'}"
                        >
                            <StatsCols :rangeType="filters.rangeType" :stats="stats.stats" />
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
