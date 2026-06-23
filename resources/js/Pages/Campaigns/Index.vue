<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import CampaignFilter from "@/Pages/Campaigns/Partials/CampaignFilter.vue";
import CampaignModal from "@/Pages/Campaigns/Partials/CampaignModal.vue";
import SubscriptionPlanAlarm from "@/Pages/Dashboard/Partials/SubscriptionPlanAlarm.vue";
import ActionTable from "@/Components/ActionTable.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InfoTooltip from "@/Components/InfoTooltip.vue";
import {ref} from "vue";
import {router, usePage} from "@inertiajs/vue3";
import {useTrackingUsage} from "@/Composables/useTrackingUsage.js";
import {formatMap} from "@/Helpers/NumberHelpers.js";
import {campaignMetricInfo as metricInfo} from "@/Data/CampaignMetricInfo.js";

const props = defineProps({
    teamStats: Object,
    presets: Array,
    filters: Object,
    rangeTypes: Array,
    subscriptionPlanAlarm: Array,
    trackingUsageBits: Object,
    campaigns: {type: Array, default: () => []},
    projects: {type: Array, default: () => []},
});

const page = usePage();

const currentProject = props.teamStats.projects[props.filters.project_id] ?? props.teamStats.projects[0];
const {currentProjectHasTrackingUsageBits} = useTrackingUsage(currentProject, props.trackingUsageBits);

const showsRevenue = currentProjectHasTrackingUsageBits(['payments']);
const showsRegistrations = currentProjectHasTrackingUsageBits(['users']);
const showProjectColumn = props.projects.length > 1 && (props.filters.project_id ?? 0) === 0;
const defaultProjectId = props.projects.find(project => project.id === props.filters.project_id)?.id ?? props.projects[0]?.id;

const columnCount = 2 + (showProjectColumn ? 1 : 0) + (showsRevenue ? 3 : 0) + (showsRegistrations ? 1 : 0);

const currency = (cents, code = page.props.teamCurrency) => formatMap.currency(cents, {notation: 'standard'}, code);
const roas = (value) => value === null ? '–' : `${value.toFixed(2)}×`;

const showCampaignModal = ref(false);

const openCampaign = (row) => {
    router.get(route('campaigns.show', {campaign: row.campaign_id, ...props.filters}));
};
</script>

<template>
    <AppLayout title="Campaigns" :fullscreen="false">
        <div v-if="subscriptionPlanAlarm.length > 0" class="mb-6">
            <SubscriptionPlanAlarm :subscriptionPlanAlarm="subscriptionPlanAlarm" />
        </div>

        <div class="max-w-5xl mx-auto space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Campaigns</h1>
                <CampaignFilter
                    :teamStats="teamStats"
                    :filters="filters"
                    :presets="presets"
                    :rangeTypes="rangeTypes"
                    submitRoute="campaigns"
                />
            </div>

            <ActionTable :links="[]" maxWidth="max-w-5xl">
                <template #title>Campaign profitability</template>
                <template #description>What each campaign earned against what you spent on it, for the selected range. Click a campaign to manage its ad spend.</template>
                <template #aside>
                    <PrimaryButton @click="showCampaignModal = true">Add campaign</PrimaryButton>
                </template>

                <template #content>
                    <tr class="text-left font-bold text-gray-700 dark:text-dark-300">
                        <th class="pb-4 pt-6 px-6">Campaign</th>
                        <th v-if="showProjectColumn" class="pb-4 pt-6 px-6">Project</th>
                        <th class="pb-4 pt-6 px-6 text-right">
                            <span class="inline-flex items-center justify-end gap-1">Spend <InfoTooltip :title="metricInfo.spend.title" :text="metricInfo.spend.text" /></span>
                        </th>
                        <th v-if="showsRevenue" class="pb-4 pt-6 px-6 text-right">
                            <span class="inline-flex items-center justify-end gap-1">Revenue <InfoTooltip :title="metricInfo.revenue.title" :text="metricInfo.revenue.text" /></span>
                        </th>
                        <th v-if="showsRevenue" class="pb-4 pt-6 px-6 text-right">
                            <span class="inline-flex items-center justify-end gap-1">Net profit <InfoTooltip :title="metricInfo.netProfit.title" :text="metricInfo.netProfit.text" /></span>
                        </th>
                        <th v-if="showsRevenue" class="pb-4 pt-6 px-6 text-right">
                            <span class="inline-flex items-center justify-end gap-1">ROAS <InfoTooltip :title="metricInfo.roas.title" :text="metricInfo.roas.text" /></span>
                        </th>
                        <th v-if="showsRegistrations" class="pb-4 pt-6 px-6 text-right">
                            <span class="inline-flex items-center justify-end gap-1">CAC <InfoTooltip :title="metricInfo.cac.title" :text="metricInfo.cac.text" /></span>
                        </th>
                        <th class="pb-4 pt-6 px-6"></th>
                    </tr>
                    <tr
                        v-for="row in campaigns"
                        :key="row.campaign_id + '-' + row.name"
                        class="cursor-pointer hover:bg-gray-50 dark:hover:bg-dark-700 text-gray-700 dark:text-dark-300"
                        @click="openCampaign(row)"
                    >
                        <td class="border-t px-6 py-4 font-medium">{{ row.name }}</td>
                        <td v-if="showProjectColumn" class="border-t px-6 py-4 text-gray-500 dark:text-dark-400">{{ row.project_name }}</td>
                        <td class="border-t px-6 py-4 text-right tabular-nums">{{ currency(row.spend) }}</td>
                        <td v-if="showsRevenue" class="border-t px-6 py-4 text-right tabular-nums">{{ currency(row.net) }}</td>
                        <td
                            v-if="showsRevenue"
                            class="border-t px-6 py-4 text-right tabular-nums font-medium"
                            :class="row.net_profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                        >
                            {{ currency(row.net_profit) }}
                        </td>
                        <td v-if="showsRevenue" class="border-t px-6 py-4 text-right tabular-nums">{{ roas(row.roas) }}</td>
                        <td v-if="showsRegistrations" class="border-t px-6 py-4 text-right tabular-nums">{{ row.cac === null ? '–' : currency(row.cac) }}</td>
                        <td class="w-px border-t px-6 py-4 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                            </svg>
                        </td>
                    </tr>
                    <tr v-if="!campaigns.length">
                        <td class="border-t px-6 py-8 text-center text-gray-500 dark:text-dark-400" :colspan="columnCount + 1">
                            No campaigns yet. Add a campaign to enter its ad spend and start tracking ROAS, CAC and net profit.
                        </td>
                    </tr>
                </template>
            </ActionTable>
        </div>

        <CampaignModal
            :show="showCampaignModal"
            :projects="projects"
            :defaultProjectId="defaultProjectId"
            @close="showCampaignModal = false"
        />
    </AppLayout>
</template>
