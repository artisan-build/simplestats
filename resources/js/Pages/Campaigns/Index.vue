<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import CampaignFilter from "@/Pages/Campaigns/Partials/CampaignFilter.vue";
import CampaignModal from "@/Pages/Campaigns/Partials/CampaignModal.vue";
import SubscriptionPlanAlarm from "@/Pages/Dashboard/Partials/SubscriptionPlanAlarm.vue";
import ActionTable from "@/Components/ActionTable.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InfoTooltip from "@/Components/InfoTooltip.vue";
import DashboardIcon from "bootstrap-icons/icons/grid.svg?component";
import Wallet2Icon from "bootstrap-icons/icons/wallet2.svg?component";
import RetentionIcon from "bootstrap-icons/icons/activity.svg?component";
import {onMounted, ref} from "vue";
import {router, usePage} from "@inertiajs/vue3";
import {useUsage} from "@/Composables/useUsage.js";
import {useOnboardingTour} from "@/Composables/useOnboardingTour.js";
import {formatMap} from "@/Helpers/NumberHelpers.js";
import {formatRoas as roas, profitClass, roasClass} from "@/Helpers/CampaignKpiHelpers.js";
import {campaignMetricInfo as metricInfo} from "@/Data/CampaignMetricInfo.js";

const props = defineProps({
    teamStats: Object,
    presets: Array,
    filters: Object,
    rangeTypes: Array,
    subscriptionPlanAlarm: Array,
    usageBits: Object,
    campaigns: {type: Array, default: () => []},
    projects: {type: Array, default: () => []},
});

const page = usePage();

const currentProject = props.teamStats.projects[props.filters.project_id] ?? props.teamStats.projects[0];
const {currentProjectHasUsages} = useUsage(currentProject, props.usageBits);

const showsRevenue = currentProjectHasUsages(['payments']);
const showsRegistrations = currentProjectHasUsages(['users']);
const showProjectColumn = props.projects.length > 1 && (props.filters.project_id ?? 0) === 0;
const defaultProjectId = props.projects.find(project => project.id === props.filters.project_id)?.id ?? props.projects[0]?.id;

const columnCount = 2 + (showProjectColumn ? 1 : 0) + (showsRevenue ? 3 : 0) + (showsRegistrations ? 1 : 0);

const currency = (cents, code = page.props.teamCurrency) => formatMap.currency(cents, {notation: 'standard'}, code);

const showCampaignModal = ref(false);

const manageCampaign = (row) => {
    router.get(route('campaigns.show', {campaign: row.campaign_id, ...props.filters}));
};

const analyzeCampaign = (row) => {
    router.get(route('dashboard', {...props.filters, project_id: row.project_id, campaign: row.campaign_id}));
};

// Only projects that track subscriptions have a revenue view to drill into.
const subscriptionBit = props.usageBits.subscription;
const tracksSubscription = (projectId) => !! ((props.teamStats.projects[projectId]?.usage ?? 0) & subscriptionBit);

const usersBit = props.usageBits.users;
const tracksUsers = (projectId) => !! ((props.teamStats.projects[projectId]?.usage ?? 0) & usersBit);

const analyzeCampaignRevenue = (row) => {
    router.get(route('revenue', {...props.filters, project_id: row.project_id, campaign: row.campaign_id}));
};

const analyzeCampaignRetention = (row) => {
    router.get(route('retention', {...props.filters, project_id: row.project_id, campaign: row.campaign_id}));
};

const {startCampaignsTour} = useOnboardingTour();

onMounted(() => startCampaignsTour());
</script>

<template>
    <AppLayout title="Campaigns" :fullscreen="false">
        <div v-if="subscriptionPlanAlarm.length > 0" class="mb-6">
            <SubscriptionPlanAlarm :subscriptionPlanAlarm="subscriptionPlanAlarm" />
        </div>

        <div class="max-w-5xl mx-auto space-y-6">
            <div class="space-y-3" data-tour="campaigns-intro">
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
                <h2 class="text-base text-gray-500 dark:text-dark-400">
                    Use your favorite ad portal for <span class="italic font-semibold">targeting</span>, SimpleStats for <span class="italic font-semibold">analysing</span>!
                </h2>
                <p class="text-sm text-gray-500 dark:text-dark-400">
                    No tracking pixel, GDPR compliant and immune to ad blockers: enter what you spent on Meta, Google &amp; co. and see each campaign's real, server-side measured revenue, ROAS, CAC and net profit, the numbers your ad dashboards can't show you.
                </p>
            </div>

            <ActionTable :links="[]" maxWidth="max-w-5xl">
                <template #title>Campaign profitability</template>
                <template #description>Spend versus the revenue it generated, for the selected range. Click a campaign to manage its ad spend.</template>
                <template #aside>
                    <PrimaryButton data-tour="add-campaign" class="shrink-0 whitespace-nowrap" @click="showCampaignModal = true">Track a campaign</PrimaryButton>
                </template>

                <template #content>
                    <tr class="text-left font-bold text-gray-700 dark:text-dark-300" data-tour="campaign-metrics">
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
                        class="hover:bg-gray-50 dark:hover:bg-dark-700 text-gray-700 dark:text-dark-300"
                    >
                        <td class="border-t px-6 py-4 font-medium">
                            <button type="button" class="text-left text-indigo-600 dark:text-indigo-400 hover:underline" v-tooltip="'Manage ad spend'" @click="manageCampaign(row)">
                                {{ row.name }}
                            </button>
                        </td>
                        <td v-if="showProjectColumn" class="border-t px-6 py-4 text-gray-500 dark:text-dark-400">{{ row.project_name }}</td>
                        <td class="border-t px-6 py-4 text-right tabular-nums">{{ currency(row.spend) }}</td>
                        <td v-if="showsRevenue" class="border-t px-6 py-4 text-right tabular-nums">{{ currency(row.net) }}</td>
                        <td
                            v-if="showsRevenue"
                            class="border-t px-6 py-4 text-right tabular-nums font-medium"
                            :class="profitClass(row.net_profit)"
                        >
                            {{ currency(row.net_profit) }}
                        </td>
                        <td v-if="showsRevenue" class="border-t px-6 py-4 text-right tabular-nums font-medium" :class="roasClass(row.roas)">{{ roas(row.roas) }}</td>
                        <td v-if="showsRegistrations" class="border-t px-6 py-4 text-right tabular-nums">{{ row.cac === null ? '–' : currency(row.cac) }}</td>
                        <td class="w-px border-t px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" aria-label="Analyze in dashboard" class="text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400" v-tooltip="'Analyze in dashboard'" @click="analyzeCampaign(row)">
                                    <DashboardIcon class="size-5" />
                                </button>
                                <button v-if="tracksSubscription(row.project_id)" type="button" aria-label="Analyze revenue" class="text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400" v-tooltip="'Analyze revenue'" @click="analyzeCampaignRevenue(row)">
                                    <Wallet2Icon class="size-5" />
                                </button>
                                <button v-if="tracksUsers(row.project_id)" type="button" aria-label="Analyze retention" class="text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400" v-tooltip="'Analyze retention'" @click="analyzeCampaignRetention(row)">
                                    <RetentionIcon class="size-5" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!campaigns.length">
                        <td class="border-t px-6 py-8 text-center text-gray-500 dark:text-dark-400" :colspan="columnCount + 1">
                            No campaigns yet. Track a campaign to enter its ad spend and start measuring ROAS, CAC and net profit.
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
