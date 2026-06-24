<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import CampaignFilter from "@/Pages/Campaigns/Partials/CampaignFilter.vue";
import SubscriptionPlanAlarm from "@/Pages/Dashboard/Partials/SubscriptionPlanAlarm.vue";
import ActionTable from "@/Components/ActionTable.vue";
import AdSpendModal from "@/Pages/Campaigns/Partials/AdSpendModal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import InfoTooltip from "@/Components/InfoTooltip.vue";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";
import {Link, router, useForm, usePage} from "@inertiajs/vue3";
import {ref} from "vue";
import {useTrackingUsage} from "@/Composables/useTrackingUsage.js";
import {formatMap} from "@/Helpers/NumberHelpers.js";
import {formatRoas, profitClass as profitClassBase, roasClass as roasClassBase} from "@/Helpers/CampaignKpiHelpers.js";
import {campaignMetricInfo as metricInfo} from "@/Data/CampaignMetricInfo.js";

const props = defineProps({
    teamStats: Object,
    presets: Array,
    filters: Object,
    rangeTypes: Array,
    subscriptionPlanAlarm: Array,
    trackingUsageBits: Object,
    campaign: Object,
    summary: Object,
    adSpends: Object,
    linkedTrackCampaigns: {type: Array, default: () => []},
    linkableTrackCampaigns: {type: Array, default: () => []},
});

const page = usePage();

const currentProject = props.teamStats.projects[props.campaign.project_id] ?? props.teamStats.projects[0];
const {currentProjectHasTrackingUsageBits} = useTrackingUsage(currentProject, props.trackingUsageBits);

const showsRevenue = currentProjectHasTrackingUsageBits(['payments']);
const showsRegistrations = currentProjectHasTrackingUsageBits(['users']);

const neutral = 'text-gray-950 dark:text-white';
const currency = (cents, code = page.props.teamCurrency) => formatMap.currency(cents, {notation: 'standard'}, code);
const roas = formatRoas;
const roasClass = (value) => roasClassBase(value, neutral);
const profitClass = (value) => profitClassBase(value, neutral);

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
        },
    });
};

const linkForm = useForm({name: ''});

const link = () => {
    linkForm.post(route('campaigns.track-campaigns.link', {campaign: props.campaign.id}), {
        preserveScroll: true,
        onSuccess: () => linkForm.reset(),
    });
};

const unlink = (trackCampaign) => {
    router.delete(route('campaigns.track-campaigns.unlink', {campaign: props.campaign.id, trackCampaign: trackCampaign.id}), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :title="campaign.name" :fullscreen="false">
        <div v-if="subscriptionPlanAlarm.length > 0" class="mb-6">
            <SubscriptionPlanAlarm :subscriptionPlanAlarm="subscriptionPlanAlarm" />
        </div>

        <div class="max-w-5xl mx-auto space-y-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-1">
                    <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                        Campaign: <span class="italic">{{ campaign.name }}</span>
                    </h1>
                    <Link
                        :href="route('dashboard', {...filters, project_id: campaign.project_id, campaign: campaign.id})"
                        class="inline-flex items-center gap-1 text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
                    >
                        Analyse this campaign in the dashboard
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0-6.75-6.75M19.5 12l-6.75 6.75" />
                        </svg>
                    </Link>
                </div>
                <CampaignFilter
                    :teamStats="teamStats"
                    :filters="filters"
                    :presets="presets"
                    :rangeTypes="rangeTypes"
                    submitRoute="campaigns.show"
                    :submitRouteParams="{campaign: campaign.id}"
                />
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                <div class="bg-white dark:bg-dark-800 border rounded-md py-3 px-4">
                    <div class="flex items-center gap-1 text-sm font-medium tracking-wider text-gray-700 dark:text-dark-400">
                        Spend <InfoTooltip :title="metricInfo.spend.title" :text="metricInfo.spend.text" />
                    </div>
                    <div class="mt-1 text-xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ currency(summary.spend) }}</div>
                </div>
                <div v-if="showsRevenue" class="bg-white dark:bg-dark-800 border rounded-md py-3 px-4">
                    <div class="flex items-center gap-1 text-sm font-medium tracking-wider text-gray-700 dark:text-dark-400">
                        Revenue <InfoTooltip :title="metricInfo.revenue.title" :text="metricInfo.revenue.text" />
                    </div>
                    <div class="mt-1 text-xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ currency(summary.net) }}</div>
                </div>
                <div v-if="showsRevenue" class="bg-white dark:bg-dark-800 border rounded-md py-3 px-4">
                    <div class="flex items-center gap-1 text-sm font-medium tracking-wider text-gray-700 dark:text-dark-400">
                        Net profit <InfoTooltip :title="metricInfo.netProfit.title" :text="metricInfo.netProfit.text" />
                    </div>
                    <div class="mt-1 text-xl font-semibold tracking-tight" :class="profitClass(summary.net_profit)">
                        {{ currency(summary.net_profit) }}
                    </div>
                </div>
                <div v-if="showsRevenue" class="bg-white dark:bg-dark-800 border rounded-md py-3 px-4">
                    <div class="flex items-center gap-1 text-sm font-medium tracking-wider text-gray-700 dark:text-dark-400">
                        ROAS <InfoTooltip :title="metricInfo.roas.title" :text="metricInfo.roas.text" />
                    </div>
                    <div class="mt-1 text-xl font-semibold tracking-tight" :class="roasClass(summary.roas)">{{ roas(summary.roas) }}</div>
                </div>
                <div v-if="showsRegistrations" class="bg-white dark:bg-dark-800 border rounded-md py-3 px-4">
                    <div class="flex items-center gap-1 text-sm font-medium tracking-wider text-gray-700 dark:text-dark-400">
                        CAC <InfoTooltip :title="metricInfo.cac.title" :text="metricInfo.cac.text" />
                    </div>
                    <div class="mt-1 text-xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ summary.cac === null ? '–' : currency(summary.cac) }}</div>
                </div>
            </div>

            <div class="bg-white dark:bg-dark-800 border dark:border-dark-700 rounded-md">
                <div class="px-4 py-3 border-b dark:border-dark-700">
                    <h2 class="font-semibold">Linked utm_campaign values</h2>
                    <p class="text-sm text-gray-500 dark:text-dark-400">
                        Traffic and revenue tracked under these <span class="font-mono">utm_campaign</span> values counts toward this campaign.<br>Link one before launch and it attaches automatically once the first visitor arrives.
                    </p>
                </div>

                <div class="p-4 space-y-3">
                    <div v-if="linkedTrackCampaigns.length" class="flex flex-wrap gap-2">
                        <Link
                            v-for="utm in linkedTrackCampaigns"
                            :key="utm.id"
                            :href="route('dashboard', {...filters, project_id: campaign.project_id, track_campaign: utm.id})"
                            class="inline-flex items-center gap-1 bg-indigo-100 dark:bg-indigo-500 dark:text-dark-100 hover:bg-indigo-200 dark:hover:bg-indigo-400 border rounded-md px-2 py-1 text-sm font-mono"
                            title="Analyse this utm_campaign in the dashboard"
                        >
                            {{ utm.name }}
                            <button type="button" class="text-indigo-400 hover:text-red-700 dark:text-indigo-100 dark:hover:text-red-300" title="Unlink" @click.stop.prevent="unlink(utm)">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </Link>
                    </div>
                    <p v-else class="text-sm text-gray-500 dark:text-dark-400">
                        No utm_campaign values linked yet. Revenue stays at zero until you link at least one.
                    </p>

                    <form @submit.prevent="link" class="flex items-center gap-2">
                        <div class="flex-1 max-w-sm">
                            <TextInput
                                v-model="linkForm.name"
                                type="text"
                                list="linkable-utms"
                                class="block w-full font-mono"
                                placeholder="exact utm_campaign value"
                            />
                            <datalist id="linkable-utms">
                                <option v-for="utm in linkableTrackCampaigns" :key="utm.id" :value="utm.name" />
                            </datalist>
                        </div>
                        <SecondaryButton type="submit" :class="{ 'opacity-25': linkForm.processing }" :disabled="linkForm.processing">
                            Link
                        </SecondaryButton>
                        <div>
                            <InputError :message="linkForm.errors.name" class="mt-1" />
                        </div>
                    </form>
                </div>
            </div>

            <ActionTable :links="adSpends.links" maxWidth="max-w-5xl">
                <template #title>Ad spend entries</template>
                <template #description>All budgets for this campaign. Click a row to edit it.</template>
                <template #aside>
                    <PrimaryButton @click="openAdd">Add ad spend</PrimaryButton>
                </template>

                <template #content>
                    <tr class="text-left font-bold text-gray-700 dark:text-dark-300">
                        <th class="pb-4 pt-6 px-6">Amount</th>
                        <th class="pb-4 pt-6 px-6">Period</th>
                        <th class="pb-4 pt-6 px-6"></th>
                    </tr>
                    <tr
                        v-for="spend in adSpends.data"
                        :key="spend.id"
                        class="cursor-pointer hover:bg-gray-50 dark:hover:bg-dark-700 text-gray-700 dark:text-dark-300"
                        @click="openEdit(spend)"
                    >
                        <td class="border-t px-6 py-4 tabular-nums">{{ currency(spend.amount, spend.currency) }}</td>
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
                    <tr v-if="!adSpends.data.length">
                        <td class="border-t px-6 py-8 text-center text-gray-500 dark:text-dark-400" colspan="3">
                            No ad spend yet. Add a budget to track this campaign's profitability.
                        </td>
                    </tr>
                </template>
            </ActionTable>
        </div>

        <AdSpendModal
            :show="showModal"
            :editSpend="editSpend"
            :campaign="campaign"
            :teamCurrency="page.props.teamCurrency"
            @close="showModal = false"
        />

        <ConfirmationModal :show="deleting !== null" @close="deleting = null">
            <template #title>Delete ad spend</template>
            <template #content>
                Are you sure you want to delete this ad spend entry for <span class="font-bold">{{ campaign.name }}</span>?
            </template>
            <template #footer>
                <SecondaryButton @click="deleting = null">Cancel</SecondaryButton>
                <DangerButton class="ms-3" @click="remove">Delete</DangerButton>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>
