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

const props = defineProps({
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
<!--        <template #header>Dashboard</template>-->

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
            <div class="grid gap-8">
                <div>
                    <StatsCharts
                        :filters="filters"
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
        </div>
    </AppLayout>
</template>
