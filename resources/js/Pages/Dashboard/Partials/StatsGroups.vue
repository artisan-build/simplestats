<script setup>
import StatsGroup from "@/Pages/Dashboard/Partials/StatsGroup.vue";
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    filters: Object,
    statsGroups: Object,
    topReferers: Object,
});

const page = usePage();
</script>

<template>
    <div class="grid gap-8 grid-cols-1">
        <div v-for="(statsGroup, statsGroupType) in statsGroups" :key="statsGroup.id"
             class="flex items-stretch bg-white dark:bg-gray-800 border shadow-md rounded-md"
        >
            <StatsGroup
                :filters="filters"
                :statsGroup="statsGroup"
                :statsGroupType="statsGroupType"
                :statsGroupSelected="page.props.auth.user.settings?.selectedGroupTypes[filters.projectId][statsGroupType]"
                :topReferers="topReferers"
            />
        </div>
    </div>
</template>
