<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsCols from "@/Pages/Dashboard/Partials/StatsCols.vue";
import StatsFilter from "@/Pages/Dashboard/Partials/StatsFilter.vue";
import StatsTop from "@/Pages/Dashboard/Partials/StatsTop.vue";
import {ARPXAU, XAAPU, XAPU, XAU} from "@/Helpers/StringHelpers.js";

defineProps({
    viewModes: Array,
    teamStats: Object,
    filters: Object,
    rangeTypes: Array,
    trackAllKey: Number,
    trackingSources: Array,
    trackingMedia: Array,
    trackingCampaigns: Array,
    trackingTerms: Array,
    trackingContents: Array,
});

</script>

<template>
    <AppLayout title="Dashboard" :fullscreen="true">
        <template #header>Dashboard</template>
        <div>
            <div class="mb-8">
                <StatsTop :viewMode="filters.viewMode" :teamStats="teamStats" />
            </div>
            <div class="mb-8">
                <StatsFilter
                    :viewModes="viewModes"
                    :teamStats="teamStats"
                    :filters="filters"
                    :rangeTypes="rangeTypes"
                    :trackAllKey="trackAllKey"
                    :trackingSources="trackingSources"
                    :trackingMedia="trackingMedia"
                    :trackingCampaigns="trackingCampaigns"
                    :trackingTerms="trackingTerms"
                    :trackingContents="trackingContents"
                />
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-md shadow overflow-x-auto mb-8 border p-1">
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
                            <th class="border-r border-gray-200 pr-1">Regs.</th>
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
                            <th class="border-r border-gray-200 pr-1">Regs.</th>
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
                            <StatsCols :stats="stats.stats" />
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
