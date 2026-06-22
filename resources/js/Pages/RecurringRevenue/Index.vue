<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsFilter from "@/Pages/Dashboard/Partials/StatsFilter.vue";
import HeadlineKpiBar from "@/Pages/Dashboard/Partials/HeadlineKpiBar.vue";
import RetentionKpiBar from "@/Pages/Dashboard/Partials/RetentionKpiBar.vue";
import MrrMovementsChart from "@/Pages/Dashboard/Partials/MrrMovementsChart.vue";
import SubscriptionPlanAlarm from "@/Pages/Dashboard/Partials/SubscriptionPlanAlarm.vue";
import {onMounted, onUnmounted, ref} from "vue";

const props = defineProps({
    teamStats: Object,
    presets: Array,
    allPresets: Array,
    hiddenPresets: Array,
    customPresets: Array,
    filters: Object,
    statsGroups: Object,
    statsGroupNames: Object,
    topReferers: Object,
    rangeTypes: Array,
    subscriptionPlanAlarm: Array,
    trackingUsageBits: Object,
    comparisonTypes: Object,
    mrr: {type: Object, default: null},
    mrr_movements: {type: Array, default: null},
    mrr_retention: {type: Object, default: null},
});

const isScrolled = ref(false);

const handleScroll = () => {
    isScrolled.value = window.scrollY > 0;
};

onMounted(() => window.addEventListener('scroll', handleScroll));
onUnmounted(() => window.removeEventListener('scroll', handleScroll));
</script>

<template>
    <AppLayout title="Recurring Revenue" :fullscreen="true">
        <div v-if="subscriptionPlanAlarm.length > 0" class="mb-6">
            <SubscriptionPlanAlarm :subscriptionPlanAlarm="subscriptionPlanAlarm" />
        </div>
        <div>
            <div class="sticky top-0 px-4 py-2 -mx-4 -my-2 mb-2 bg-gray-50 dark:bg-dark-900 z-20" :class="{'border-b dark:border-dark-700 shadow-sm': isScrolled}">
                <StatsFilter
                    :teamStats="teamStats"
                    :statsGroups="statsGroups"
                    :statsGroupNames="statsGroupNames"
                    :presets="presets"
                    :allPresets="allPresets"
                    :hiddenPresets="hiddenPresets"
                    :customPresets="customPresets"
                    :filters="filters"
                    :rangeTypes="rangeTypes"
                    :topReferers="topReferers"
                    :trackingUsageBits="trackingUsageBits"
                    :comparisonTypes="comparisonTypes"
                    submitRoute="recurring-revenue"
                />
            </div>
            <div v-if="mrr_movements?.length" class="grid gap-4">
                <HeadlineKpiBar v-if="mrr" :mrr="mrr" :filters="filters" />
                <RetentionKpiBar v-if="mrr_retention" :retention="mrr_retention" :filters="filters" />
                <MrrMovementsChart :movements="mrr_movements" :filters="filters" />
            </div>
            <div v-else class="bg-white dark:bg-dark-800 border rounded-md p-8 text-center text-gray-500 dark:text-dark-400">
                This project does not track payments, so there is no recurring revenue to show.
            </div>
        </div>
    </AppLayout>
</template>
