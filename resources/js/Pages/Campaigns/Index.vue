<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatsFilter from "@/Pages/Dashboard/Partials/StatsFilter.vue";
import SubscriptionPlanAlarm from "@/Pages/Dashboard/Partials/SubscriptionPlanAlarm.vue";
import AdSpendModal from "@/Pages/Campaigns/Partials/AdSpendModal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import ActionTable from "@/Components/ActionTable.vue";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";
import InfoTooltip from "@/Components/InfoTooltip.vue";
import {onMounted, onUnmounted, ref} from "vue";
import {router, usePage} from "@inertiajs/vue3";
import {useTrackingUsage} from "@/Composables/useTrackingUsage.js";
import {formatMap} from "@/Helpers/NumberHelpers.js";

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
    campaigns: {type: Array, default: () => []},
    adSpends: {type: Array, default: () => []},
    availableCampaigns: {type: Array, default: () => []},
});

const page = usePage();

const currentProject = props.teamStats.projects[props.filters.project_id] ?? props.teamStats.projects[0];
const {currentProjectHasTrackingUsageBits} = useTrackingUsage(currentProject, props.trackingUsageBits);

const showsRevenue = currentProjectHasTrackingUsageBits(['payments']);
const showsRegistrations = currentProjectHasTrackingUsageBits(['users']);

const projectOptions = Object.values(props.teamStats.projects)
    .filter(project => project.id !== 0)
    .map(project => ({id: project.id, name: project.name}));

const showProjectColumn = projectOptions.length > 1;
const defaultProjectId = props.filters.project_id || projectOptions[0]?.id;
const projectName = (id) => projectOptions.find(project => project.id === id)?.name ?? '–';

const currency = (cents, code = page.props.teamCurrency) => formatMap.currency(cents, {notation: 'standard'}, code);
const roas = (value) => value === null ? '–' : `${value.toFixed(2)}×`;

const metricInfo = {
    spend: {title: 'Ad Spend', text: 'What you paid for ads on this campaign during the selected period. Entered manually via "Add ad spend".'},
    revenue: {title: 'Net Revenue', text: 'Net revenue (after fees) from customers this campaign brought in, during the selected period.'},
    netProfit: {title: 'Net Profit', text: 'Revenue minus ad spend. Green means the campaign earned more than it cost, red means it cost more than it earned.'},
    roas: {title: 'Return On Ad Spend (ROAS)', text: 'Revenue divided by spend. 2.00× means you earned 2 € back for every 1 € spent. Above 1.00× is profitable.'},
    cac: {title: 'Customer Acquisition Cost (CAC)', text: 'Ad spend divided by new registrations. The average cost of acquiring one new sign-up.'},
};

const showModal = ref(false);
const editSpend = ref(null);
const deleting = ref(null);

const openAdd = () => {
    editSpend.value = null;
    showModal.value = true;
};

const openEdit = (spend) => {
    editSpend.value = spend;
    showModal.value = true;
};

const remove = () => {
    router.delete(route('ad-spends.destroy', deleting.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleting.value = null;
            router.reload({only: ['campaigns', 'adSpends']});
        },
    });
};

const isScrolled = ref(false);
const handleScroll = () => {
    isScrolled.value = window.scrollY > 0;
};

onMounted(() => window.addEventListener('scroll', handleScroll));
onUnmounted(() => window.removeEventListener('scroll', handleScroll));
</script>

<template>
    <AppLayout title="Campaigns" :fullscreen="true">
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
                    submitRoute="campaigns"
                />
            </div>

            <div class="grid gap-8">
                <div class="bg-white dark:bg-dark-800 border dark:border-dark-700 rounded-md">
                    <div class="px-4 py-3 border-b dark:border-dark-700">
                        <h2 class="font-semibold">Campaign profitability</h2>
                    </div>

                    <div v-if="campaigns.length" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-left text-gray-500 dark:text-dark-400 border-b dark:border-dark-700">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Campaign</th>
                                    <th class="px-4 py-3 font-medium">
                                        <span class="inline-flex items-center justify-end gap-1 w-full">Spend <InfoTooltip :title="metricInfo.spend.title" :text="metricInfo.spend.text" /></span>
                                    </th>
                                    <th v-if="showsRevenue" class="px-4 py-3 font-medium">
                                        <span class="inline-flex items-center justify-end gap-1 w-full">Revenue (net) <InfoTooltip :title="metricInfo.revenue.title" :text="metricInfo.revenue.text" /></span>
                                    </th>
                                    <th v-if="showsRevenue" class="px-4 py-3 font-medium">
                                        <span class="inline-flex items-center justify-end gap-1 w-full">Net profit <InfoTooltip :title="metricInfo.netProfit.title" :text="metricInfo.netProfit.text" /></span>
                                    </th>
                                    <th v-if="showsRevenue" class="px-4 py-3 font-medium">
                                        <span class="inline-flex items-center justify-end gap-1 w-full">ROAS <InfoTooltip :title="metricInfo.roas.title" :text="metricInfo.roas.text" /></span>
                                    </th>
                                    <th v-if="showsRegistrations" class="px-4 py-3 font-medium">
                                        <span class="inline-flex items-center justify-end gap-1 w-full">CAC <InfoTooltip :title="metricInfo.cac.title" :text="metricInfo.cac.text" /></span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in campaigns"
                                    :key="row.campaign_id + '-' + row.name"
                                    class="border-b last:border-0 dark:border-dark-700"
                                >
                                    <td class="px-4 py-3">{{ row.name }}</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ currency(row.spend) }}</td>
                                    <td v-if="showsRevenue" class="px-4 py-3 text-right tabular-nums">{{ currency(row.net) }}</td>
                                    <td
                                        v-if="showsRevenue"
                                        class="px-4 py-3 text-right tabular-nums font-medium"
                                        :class="row.net_profit >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                                    >
                                        {{ currency(row.net_profit) }}
                                    </td>
                                    <td v-if="showsRevenue" class="px-4 py-3 text-right tabular-nums">{{ roas(row.roas) }}</td>
                                    <td v-if="showsRegistrations" class="px-4 py-3 text-right tabular-nums">{{ row.cac === null ? '–' : currency(row.cac) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="p-8 text-center text-gray-500 dark:text-dark-400">
                        No campaign data for this range yet. Add ad spend for a campaign to see ROAS, CAC and net profit.
                    </div>
                </div>

                <ActionTable :links="[]">
                    <template #title>Ad spend</template>
                    <template #description>The budgets behind the numbers above. Click a row to edit it.</template>
                    <template #aside>
                        <PrimaryButton v-if="availableCampaigns.length" @click="openAdd">Add ad spend</PrimaryButton>
                    </template>

                    <template #content>
                        <tr class="text-left font-bold text-gray-700 dark:text-dark-300">
                            <th class="pb-4 pt-6 px-6">Campaign</th>
                            <th v-if="showProjectColumn" class="pb-4 pt-6 px-6">Project</th>
                            <th class="pb-4 pt-6 px-6 text-right">Amount</th>
                            <th class="pb-4 pt-6 px-6">Period</th>
                            <th class="pb-4 pt-6 px-6"></th>
                        </tr>
                        <tr
                            v-for="spend in adSpends"
                            :key="spend.id"
                            class="cursor-pointer hover:bg-gray-50 dark:hover:bg-dark-700 text-gray-700 dark:text-dark-300"
                            @click="openEdit(spend)"
                        >
                            <td class="border-t px-6 py-4">{{ spend.campaign_name }}</td>
                            <td v-if="showProjectColumn" class="border-t px-6 py-4">{{ projectName(spend.project_id) }}</td>
                            <td class="border-t px-6 py-4 text-right tabular-nums">{{ currency(spend.amount, spend.currency) }}</td>
                            <td class="border-t px-6 py-4 text-gray-500 dark:text-dark-400">{{ spend.starts_on }} → {{ spend.ends_on }}</td>
                            <td class="w-px border-t px-6 py-4">
                                <button
                                    type="button"
                                    class="text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                                    title="Delete"
                                    @click.stop="deleting = spend"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!adSpends.length">
                            <td class="border-t px-6 py-8 text-center text-gray-500 dark:text-dark-400" :colspan="showProjectColumn ? 5 : 4">
                                No ad spend yet. Add a budget for a campaign to track its profitability.
                            </td>
                        </tr>
                    </template>
                </ActionTable>
            </div>
        </div>

        <AdSpendModal
            :show="showModal"
            :editSpend="editSpend"
            :availableCampaigns="availableCampaigns"
            :projects="projectOptions"
            :defaultProjectId="defaultProjectId"
            :teamCurrency="page.props.teamCurrency"
            @close="showModal = false"
        />

        <ConfirmationModal :show="deleting !== null" @close="deleting = null">
            <template #title>Delete ad spend</template>
            <template #content>
                Are you sure you want to delete the ad spend for <span class="font-bold">{{ deleting?.campaign_name }}</span>?
            </template>
            <template #footer>
                <SecondaryButton @click="deleting = null">Cancel</SecondaryButton>
                <DangerButton class="ms-3" @click="remove">Delete</DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
