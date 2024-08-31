<script setup>
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { Link } from '@inertiajs/vue3';
import {formatNumber} from "chart.js/helpers";

defineProps({
    freePlanWarning: Object,
});

const appName = import.meta.env.VITE_APP_NAME;
</script>

<template>
    <div class="flex justify-between items-center px-5 py-3 bg-blue-500 text-white rounded-md mb-6">
        <div>
            With the free plan of {{ appName }} you can only track up to <a href="/#pricing" target="_blank" class="font-bold underline">{{ formatNumber(freePlanWarning.users) }}</a> users/regs.
            <Link class="font-bold underline" :href="route('spark.portal')">Upgrade</Link> your plan to enable limitless tracking!
        </div>
        <div>
            <div class="flex items-center gap-2">
                <div>
                    <SecondaryButton @click="$inertia.visit(route('spark.portal'))">
                        Upgrade now
                    </SecondaryButton>
                </div>
                <div>
                    <Link :href="route('stats.hide-free-plan-warning')" method="post" class="hover:text-gray-300">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
