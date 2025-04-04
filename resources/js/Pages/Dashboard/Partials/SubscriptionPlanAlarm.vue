<script setup>
import WarningBox from "@/Components/WarningBox.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

defineProps({
    subscriptionPlanAlarm: Array,
});
</script>

<template>
    <WarningBox>
        <div class="flex justify-between">
            <div class="text-base">
                <span v-for="alarm in subscriptionPlanAlarm">
                    <span class="font-bold text-red-600 dark:text-red-200">{{ alarm.text }}</span><br>
                </span>
                <span v-if="subscriptionPlanAlarm[0].percent < 100">Once you reach the limit, we will stop tracking and storing your data!</span>
                <span v-else-if="subscriptionPlanAlarm[0].percent === 100">We will now stop tracking and storing your data!</span>
                Consider upgrading your subscription plan.
            </div>
            <div>
                <SecondaryButton @click="$inertia.visit(route('spark.portal'))">
                    Upgrade now
                </SecondaryButton>
            </div>
        </div>
    </WarningBox>
</template>

<style scoped>

</style>
