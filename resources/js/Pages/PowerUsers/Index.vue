<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ActionTable from '@/Components/ActionTable.vue';
import SortableHeader from '@/Components/SortableHeader.vue';
import SelectInput from '@/Components/SelectInput.vue';
import ProjectFavicon from '@/Components/ProjectFavicon.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import InfoTooltip from '@/Components/InfoTooltip.vue';
import StatsIcon from '@/Components/StatsIcon.vue';
import SubscriptionPlanAlarm from '@/Pages/Dashboard/Partials/SubscriptionPlanAlarm.vue';
import BoxArrowUpRightIcon from 'bootstrap-icons/icons/box-arrow-up-right.svg?component';
import {ref} from 'vue';
import {router, useForm, usePage} from '@inertiajs/vue3';
import {useUsage} from '@/Composables/useUsage.js';
import {formatMap} from '@/Helpers/NumberHelpers.js';

const props = defineProps({
    teamStats: Object,
    filters: Object,
    subscriptionPlanAlarm: {type: Array, default: () => []},
    usageBits: Object,
    powerUsers: {type: Object, default: () => ({data: [], links: []})},
    sort: {type: Object, default: () => ({column: null, direction: 'desc'})},
    permissions: {type: Object, default: () => ({canUpdateProject: false})},
});

const page = usePage();

const currentProject = props.teamStats.projects[props.filters.project_id] ?? props.teamStats.projects[0];
const {currentProjectHasUsages} = useUsage(currentProject, props.usageBits);

const showsRevenue = currentProjectHasUsages(['payments']);
const showsSubscription = currentProjectHasUsages(['subscription']);

const selectedProjectId = props.filters.project_id ?? 0;
const isSpecificProject = selectedProjectId !== 0;
const showProjectColumn = props.teamStats.totalProjectsCount > 1 && !isSpecificProject;

// Base columns always present: User, Referer, Registered, Logins, Last login.
const columnCount = 5 + (showProjectColumn ? 1 : 0) + (showsSubscription ? 1 : 0) + (showsRevenue ? 1 : 0);

const currency = (cents, code = page.props.teamCurrency) => formatMap.currency(cents, {notation: 'standard'}, code);
const number = (value) => formatMap.number(value, {minimumFractionDigits: 0, maximumFractionDigits: 0});

const dateFormatter = new Intl.DateTimeFormat('en-US', {year: 'numeric', month: 'short', day: 'numeric'});
const formatDate = (value) => value ? dateFormatter.format(new Date(value)) : '–';

// Human-readable age, e.g. "3 months ago" / "today". The absolute date is shown
// small underneath.
const relativeTime = new Intl.RelativeTimeFormat('en', {numeric: 'auto'});
const since = (value) => {
    if (!value) {
        return '–';
    }
    const days = Math.round((new Date(value) - new Date()) / 86_400_000);
    const abs = Math.abs(days);
    if (abs >= 365) {
        return relativeTime.format(Math.round(days / 365), 'year');
    }
    if (abs >= 30) {
        return relativeTime.format(Math.round(days / 30), 'month');
    }
    if (abs >= 7) {
        return relativeTime.format(Math.round(days / 7), 'week');
    }
    if (abs >= 1) {
        return relativeTime.format(days, 'day');
    }
    return 'today';
};

const safe = (value, fallback = 'Unknown') => value ?? fallback;
// Seed with project + id so the same external user id in different projects does
// not collapse to the same avatar.
const avatar = (row) => `https://api.dicebear.com/9.x/avataaars/svg?seed=${encodeURIComponent(`${row.project_id}-${row.id}`)}`;

// {id} marks where the user id goes (apps put it mid-path or in a query); without
// a token we fall back to appending it, so a plain base url still works.
const impersonateLink = (row) => {
    const base = row.project?.impersonate_base_url;
    if (!base) {
        return null;
    }
    const id = encodeURIComponent(row.id);
    return base.includes('{id}') ? base.replaceAll('{id}', id) : `${base}/${id}`;
};

// Deterministic badge color per plan name so a given plan always renders the same
// hue. Full class strings (no dynamic concatenation) so Tailwind keeps them.
const planPalette = [
    'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300',
    'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
    'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
    'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300',
    'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300',
    'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300',
    'bg-teal-50 text-teal-700 dark:bg-teal-500/10 dark:text-teal-300',
    'bg-orange-50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-300',
];
const planColor = (name) => {
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = (hash * 31 + name.charCodeAt(i)) >>> 0;
    }
    return planPalette[hash % planPalette.length];
};

// Query params the sort navigation must carry over so it stays scoped to the
// selected project.
const sortParams = {project_id: selectedProjectId};

const projectId = ref(selectedProjectId);
const changeProject = () => {
    router.get(route('power-users'), {project_id: projectId.value}, {preserveScroll: true, preserveState: false});
};

const impersonateForm = useForm({
    impersonate_base_url: currentProject?.impersonate_base_url ?? '',
});
// Autosave when the field loses focus (or on Enter); dedupe identical values so a
// blur without an edit does not fire a redundant request.
const lastSavedUrl = ref(impersonateForm.impersonate_base_url);
const saveImpersonateUrl = () => {
    if (impersonateForm.impersonate_base_url === lastSavedUrl.value) {
        return;
    }
    lastSavedUrl.value = impersonateForm.impersonate_base_url;
    impersonateForm.put(route('projects.impersonate-url.update', props.filters.project_id), {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <AppLayout title="Power Users" :fullscreen="true">
        <div v-if="subscriptionPlanAlarm.length > 0" class="mb-6">
            <SubscriptionPlanAlarm :subscriptionPlanAlarm="subscriptionPlanAlarm" />
        </div>

        <div class="space-y-6">
            <div class="space-y-3">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Power Users</h1>
                    <div>
                        <InputLabel class="hidden" for="project_id" value="Project" />
                        <SelectInput
                            id="project_id"
                            v-model="projectId"
                            class="block w-48 dark:!bg-dark-800"
                            :options="teamStats.projects"
                            @change="changeProject"
                        >
                            <template #selected="{ option }">
                                <span class="flex items-center gap-2">
                                    <ProjectFavicon :domain="option?.domain" />
                                    <span class="truncate">{{ option?.name ?? 'Select...' }}</span>
                                </span>
                            </template>
                            <template #option="{ option }">
                                <span class="flex items-center gap-2">
                                    <ProjectFavicon :domain="option.domain" />
                                    <span class="truncate">{{ option.name }}</span>
                                </span>
                            </template>
                        </SelectInput>
                    </div>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="max-w-3xl space-y-1">
                        <h2 class="text-base text-gray-500 dark:text-dark-400">
                            Your most valuable users, ranked by lifetime revenue and activity.
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-dark-400">
                            GDPR compliant: users are identified only by the ID your app sends, never by name or email.
                        </p>
                    </div>

                    <div v-if="isSpecificProject && permissions.canUpdateProject" class="shrink-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center whitespace-nowrap text-sm text-gray-500 dark:text-dark-400">
                                <label for="impersonate_base_url">Impersonate URL</label>
                                <InfoTooltip
                                    title="Impersonate URL"
                                    text="Base URL to open an impersonation link in your app. Use {id} where the user ID goes; without {id} it is appended to the end."
                                />
                            </span>
                            <TextInput
                                id="impersonate_base_url"
                                v-model="impersonateForm.impersonate_base_url"
                                type="text"
                                class="w-80 text-sm"
                                placeholder="https://example.com/users/{id}/impersonate"
                                @blur="saveImpersonateUrl"
                                @keyup.enter="saveImpersonateUrl"
                            />
                            <span v-if="impersonateForm.processing" class="whitespace-nowrap text-sm text-gray-400 dark:text-dark-500">Saving…</span>
                            <span v-else-if="impersonateForm.recentlySuccessful" class="whitespace-nowrap text-sm text-green-600 dark:text-green-400">Saved</span>
                        </div>
                        <InputError :message="impersonateForm.errors.impersonate_base_url" class="mt-1" />
                    </div>
                </div>
            </div>

            <ActionTable :links="powerUsers.links" maxWidth="max-w-none">
                <template #content>
                    <tr class="text-left font-bold text-gray-700 dark:text-dark-300">
                        <th class="pb-4 pt-6 px-6">User</th>
                        <th v-if="showProjectColumn" class="pb-4 pt-6 px-6">Project</th>
                        <th class="pb-4 pt-6 px-6">Referer</th>
                        <SortableHeader
                            class="pb-4 pt-6 px-6"
                            sortKey="registered"
                            :active="sort.column"
                            :direction="sort.direction"
                            routeName="power-users"
                            :params="sortParams"
                        >Registered</SortableHeader>
                        <SortableHeader
                            class="pb-4 pt-6 px-6 text-right"
                            sortKey="logins"
                            :active="sort.column"
                            :direction="sort.direction"
                            routeName="power-users"
                            :params="sortParams"
                        >Logins</SortableHeader>
                        <SortableHeader
                            class="pb-4 pt-6 px-6"
                            sortKey="last_login"
                            :active="sort.column"
                            :direction="sort.direction"
                            routeName="power-users"
                            :params="sortParams"
                        >Last login</SortableHeader>
                        <SortableHeader
                            v-if="showsSubscription"
                            class="pb-4 pt-6 px-6"
                            sortKey="plan"
                            :active="sort.column"
                            :direction="sort.direction"
                            routeName="power-users"
                            :params="sortParams"
                        >Plan</SortableHeader>
                        <SortableHeader
                            v-if="showsRevenue"
                            class="pb-4 pt-6 px-6 text-right"
                            sortKey="revenue"
                            :active="sort.column"
                            :direction="sort.direction"
                            routeName="power-users"
                            :params="sortParams"
                        >Revenue</SortableHeader>
                        <th class="pb-4 pt-6 px-6"></th>
                    </tr>
                    <tr
                        v-for="row in powerUsers.data"
                        :key="row.project_id + '-' + row.id"
                        class="hover:bg-gray-50 dark:hover:bg-dark-700 text-gray-700 dark:text-dark-300"
                    >
                        <td class="border-t px-6 py-4">
                            <div class="flex items-center gap-3">
                                <img
                                    :src="avatar(row)"
                                    :alt="row.id"
                                    class="size-10 shrink-0 rounded-full border-2 border-gray-200 bg-gray-100 dark:border-dark-600 dark:bg-dark-700"
                                    loading="lazy"
                                />
                                <div class="text-xs">
                                    <div class="font-mono text-sm font-semibold text-gray-900 dark:text-white">{{ row.id }}</div>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-gray-500 dark:text-dark-400">
                                        <span class="flex items-center gap-1">
                                            <StatsIcon :statsName="row.stats_location_country?.name ?? null" statsType="location_country" statsIconType="country" />
                                            {{ safe(row.stats_location_country?.name) }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <StatsIcon :statsName="row.stats_device_type?.name ?? null" statsType="device_type" statsIconType="path" />
                                            {{ safe(row.stats_device_type?.name) }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <StatsIcon :statsName="row.stats_device_platform?.name ?? null" statsType="device_platform" statsIconType="path" />
                                            {{ safe(row.stats_device_platform?.name) }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <StatsIcon :statsName="row.stats_device_browser?.name ?? null" statsType="device_browser" statsIconType="path" />
                                            {{ safe(row.stats_device_browser?.name) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td v-if="showProjectColumn" class="border-t px-6 py-4 text-gray-500 dark:text-dark-400">{{ row.project?.name }}</td>
                        <td class="border-t px-6 py-4 text-gray-500 dark:text-dark-400">
                            <span class="flex items-center gap-2">
                                <StatsIcon :statsName="row.stats_track_referer?.name ?? null" statsType="track_referer" statsIconType="favicon" />
                                <span class="max-w-[160px] truncate" v-tooltip="row.stats_track_referer?.name ?? 'Unknown'">{{ safe(row.stats_track_referer?.name) }}</span>
                            </span>
                        </td>
                        <td class="border-t px-6 py-4 text-gray-500 dark:text-dark-400">
                            <div>{{ since(row.time) }}</div>
                            <div v-if="row.time" class="text-xs text-gray-400 dark:text-dark-500">({{ formatDate(row.time) }})</div>
                        </td>
                        <td class="border-t px-6 py-4 text-right tabular-nums">{{ number(row.logins_count) }}</td>
                        <td class="border-t px-6 py-4 text-gray-500 dark:text-dark-400">
                            <div>{{ since(row.last_login_at) }}</div>
                            <div v-if="row.last_login_at" class="text-xs text-gray-400 dark:text-dark-500">({{ formatDate(row.last_login_at) }})</div>
                        </td>
                        <td v-if="showsSubscription" class="border-t px-6 py-4">
                            <span
                                v-if="row.current_plan"
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="planColor(row.current_plan)"
                            >
                                {{ row.current_plan }}
                            </span>
                            <span v-else class="text-gray-400 dark:text-dark-500">–</span>
                        </td>
                        <td v-if="showsRevenue" class="border-t px-6 py-4 text-right tabular-nums font-medium">{{ currency(row.total_gross) }}</td>
                        <td class="w-px border-t px-6 py-4">
                            <div class="flex items-center justify-end">
                                <a
                                    v-if="impersonateLink(row)"
                                    :href="impersonateLink(row)"
                                    target="_blank"
                                    rel="noopener"
                                    aria-label="Impersonate user"
                                    class="text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400"
                                    v-tooltip="'Impersonate user'"
                                >
                                    <BoxArrowUpRightIcon class="size-5" />
                                </a>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!powerUsers.data.length">
                        <td class="border-t px-6 py-8 text-center text-gray-500 dark:text-dark-400" :colspan="columnCount + 1">
                            No power users yet. Once your app tracks registrations and logins, your most active users show up here.
                        </td>
                    </tr>
                </template>
            </ActionTable>
        </div>
    </AppLayout>
</template>
