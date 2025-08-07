<script setup>
import ActionSection from '@/Components/ActionSection.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import {usePage} from "@inertiajs/vue3";
import {format} from 'date-fns';

const props = defineProps({
    team: Object,
});

const page = usePage();
const used = props.team.events_count_monthly || 0
const total = page.props.currentPlanOptions?.Events || 0;

const dateRange = `${format(new Date(), 'MMMM yyyy')}`;

const getUsagePercentage = () => {
    if (total === 0) return 0;
    return Math.floor((used / total) * 100);
};
</script>

<template>
    <div>
        <div>
            <SectionBorder />

            <!-- Add Team Member -->
            <ActionSection>
                <template #title>
                    Team Events Usage
                </template>

                <template #description>
                    Here you can see your team's event usage for the <strong>current month</strong>.<br>
                    This helps you keep track and plan ahead.
                    We'll notify you when you're getting close to your plan limit.
                </template>

                <template #content>
                    <div class="text-gray-600 dark:text-dark-400">
                        <div class="w-full text-sm font-medium flex justify-between">
                        <div>Total usage - {{ dateRange }}</div>
                            <div>
                                {{ used.toLocaleString() }} / {{ total.toLocaleString() }} events
                                <span class="text-gray-500 dark:text-dark-500">({{ getUsagePercentage() }})%</span>
                            </div>
                        </div>
                        <div class="mt-3" aria-hidden="true">
                            <div class="overflow-hidden rounded-full bg-gray-200 dark:bg-dark-300">
                                <div class="h-2 rounded-full bg-indigo-500" :style="`width: ${getUsagePercentage()}%`" />
                            </div>
                        </div>
                    </div>
                </template>
            </ActionSection>
        </div>
    </div>
</template>
