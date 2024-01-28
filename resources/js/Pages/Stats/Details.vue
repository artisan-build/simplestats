<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import {Link} from "@inertiajs/vue3";
import StatsDetailsCols from "@/Pages/Stats/Partials/StatsDetailsCols.vue";
import StatsDetailsFilter from "@/Pages/Stats/Partials/StatsDetailsFilter.vue";
import StatsDetailsGraphs from "@/Pages/Stats/Partials/StatsDetailsGraphs.vue";

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
    <AppLayout title="Project Statistics" :fullscreen="true">
        <div>
            <div class="mb-8">
                <StatsDetailsGraphs :teamStats="teamStats" />
            </div>
            <div class="mb-8">
                <StatsDetailsFilter
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
                        <tr class="text-right font-bold text-gray-600 bg-gray-100">
                            <th class="p-3 text-left">Date</th>
                            <th class="w-1 bg-white"></th>
                            <th>Regs.</th>
                            <th>NAU</th>
                            <th>NAPU</th>
                            <th>DAU</th>
                            <th>DAPU</th>
                            <th class="pr-2">DAAPU</th>
                            <th class="w-1 bg-white"></th>
                            <th>Gross</th>
                            <th>Net</th>
                            <th>Fee</th>
                            <th class="pr-2">ARPDAU</th>
                            <th class="w-1 bg-white"></th>
                            <th>Regs.</th>
                            <th>Reg. to DAU</th>
                            <th>Pay User</th>
                            <th>Reg. to Pay</th>
                            <th>Tot. Actives</th>
                            <th>Reg. to Active</th>
                            <th>ARPU</th>
                            <th>ARPPU</th>
                            <th class="pr-2">Rev.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="teamStats.stats" v-for="(stats, i) in teamStats.stats" :key="stats.date"
                            class="hover:bg-gray-100 focus-within:bg-gray-50 text-gray-700 text-right"
                            :class="{'bg-gray-50': i % 2 === 1, 'border-t': i === 'total'}"
                        >
                            <StatsDetailsCols :stats="stats.stats" />
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
