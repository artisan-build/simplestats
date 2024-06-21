<script setup>
import { Link } from '@inertiajs/vue3';
import WarningBox from "@/Components/WarningBox.vue";

defineProps({
    subscriptionPlanAlarm: Array,
});
</script>

<template>
    <WarningBox>
        <div class="text-base">
            <span v-for="alarm in subscriptionPlanAlarm">
                <span class="font-bold text-red-500">{{ alarm.text }}</span><br>
            </span>
            <span v-if="subscriptionPlanAlarm[0].percent < 100">Once you reach the limit, we will stop tracking and storing your data!</span>
            Consider upgrading your subscription plan <Link class="simpleLink font-bold" :href="route('spark.portal')">here</Link>.
        </div>
    </WarningBox>
</template>

<style scoped>

</style>
