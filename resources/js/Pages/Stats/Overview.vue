<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsOverviewGroup from "@/Pages/Stats/Partials/StatsOverviewGroup.vue";

const props = defineProps({
    teamStats: Object,
});

const projects = props.teamStats.projects;
// show overall total only if there are min 2 projects
const showOverallTotal = (projects.length >= 2);
// show group total only if a project has min. 2 instances
const showGroupTotal = projects.some(project => project.instances.length >= 2);
</script>

<template>
    <AppLayout title="Statistics" :fullscreen="true">
        <div>
            <div v-for="project in projects">
                <StatsOverviewGroup :showGroupTotal="showGroupTotal && project.instances.length >= 1" :statsGroup="project" />
            </div>
            <div v-if="showOverallTotal">
                <hr class="border my-5">
                <StatsOverviewGroup :showGroupTotal="true" :statsGroup="teamStats" />
            </div>
        </div>
    </AppLayout>
</template>
