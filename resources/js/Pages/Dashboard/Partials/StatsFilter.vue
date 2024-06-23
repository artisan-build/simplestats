<script setup>
import {router, useForm, usePage} from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SelectInput from "@/Components/SelectInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import {periodSingular} from "@/Helpers/StringHelpers.js";
import pluralize from 'pluralize';

const props = defineProps({
    teamStats: Object,
    statsGroups: Object,
    statsGroupNames: Object,
    filters: Object,
    rangeTypes: Array,
});

const page = usePage();

const form = useForm(props.filters);

const rangeAmounts = Array.from({ length: 60 - 2 }, (_, i) => ({
    // start from min 3 days cause smaller would break the charts, and it not really makes sense...
    id: (i + 3).toString(),
    name: (i + 3).toString()
}));

const submit = () => {
    form.get(route('dashboard'), {preserveScroll: true});
};

const filterChanged = (event) => {
    // if a project changes, we need to reset some values, which may no longer relate to the new selected project
    if (event.target.id === 'projectId') {
        form.instanceId = 0;

        // reset group filters...
        for(let groupKey in props.statsGroups) {
            props.statsGroups[groupKey].forEach(item => {
                form[item.type] = 0;
            });
        }
    }

    submit();
};

const periodRange = periodSingular[props.filters.rangeType];

const chartDescription = `
    <div class="font-bold text-green-300">Current ${periodRange}</div>
    <div>
        As the current ${periodRange} may not be finished yet, the graphs could drastically go downwards compared to the previous ${periodRange}.
        The gray background on the charts is indicating that the current ${periodRange} is not finished yet!
    </div>
</div>
`;

const teamChanged = () => {
    router.put(route('current-team.update'), {
        team_id: page.props.auth.user.current_team_id,
    }, {
        preserveState: false,
    });
};
</script>

<template>
    <form @submit.prevent="submit">
        <div class="">
            <div class="flex flex-wrap w-full gap-x-8 gap-y-5 items-center justify-start md:justify-end">
                <div class="flex-grow">
                    <div class="flex flex-wrap gap-x-8 gap-y-5 items-center justify-between">
                        <div class="flex flex-wrap gap-x-2 items-center">
                            <div v-if="$page.props.auth.user.all_teams.length > 1">
                                <InputLabel class="hidden" for="teamId" value="Team" />
                                <SelectInput
                                    id="teamId"
                                    v-model="$page.props.auth.user.current_team_id"
                                    class="mt-1 block w-40"
                                    :options="$page.props.auth.user.all_teams"
                                    @change="teamChanged"
                                />
                            </div>
                            <div>
                                <InputLabel class="hidden" for="projectId" value="Project" />
                                <SelectInput
                                    id="projectId"
                                    v-model="form.projectId"
                                    class="mt-1 block w-40"
                                    :options="teamStats.projects"
                                    @change="filterChanged"
                                />
                            </div>
                            <div v-if="teamStats.projects[filters.projectId].instances && teamStats.totalInstancesCount > 0">
                                <InputLabel class="hidden" for="instanceId" value="Instance" />
                                <SelectInput
                                    id="instanceId"
                                    v-model="form.instanceId"
                                    class="mt-1 block w-40"
                                    :options="teamStats.projects[filters.projectId].instances"
                                    @change="filterChanged"
                                />
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-x-2 items-center">
                            <template v-for="statsGroup in statsGroups">
                                <template v-for="group in statsGroup">
                                    <div v-if="filters[group.type]" class="bg-indigo-100 border rounded-md p-2 mt-1">
                                        {{ pluralize.singular(group.title) }} is <strong>{{ statsGroupNames[group.type] }}</strong>
                                        <button class="ml-1 hover:text-red-700" @click="(e) => { form[group.type] = 0; filterChanged(e); }">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5 inline-block">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </template>
                        </div>

                        <div class="flex flex-wrap gap-x-2 items-center">
                            <div>
                                <InputLabel class="hidden" for="rangeStart" value="Start from" />
                                <TextInput
                                    type="date"
                                    id="rangeStart"
                                    v-model="form.rangeStart"
                                    class="mt-1 block w-36"
                                    @change="filterChanged"
                                />
                            </div>
                            <div>
                                <InputLabel class="hidden" for="rangeAmount" value="Last" />
                                <SelectInput
                                    id="rangeAmount"
                                    v-model="form.rangeAmount"
                                    class="mt-1 block w-18"
                                    :options="rangeAmounts"
                                    @change="filterChanged"
                                />
                            </div>
                            <div>
                                <InputLabel class="hidden" for="rangeType" value="&nbsp;" />
                                <SelectInput
                                    id="rangeType"
                                    v-model="form.rangeType"
                                    class="mt-1 block w-28"
                                    :options="rangeTypes"
                                    @change="filterChanged"
                                />
                            </div>
                            <div class="hidden sm:block">
                                <div v-tooltip.left="{ content: `<div class='flex flex-col space-y-4 max-w-64 p-2 text-sm'>${chartDescription}</div>`, html: true }" class="text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="">
                                <InputLabel class="hidden" for="refresh" value="&nbsp;" />
                                <SecondaryButton type="submit" style="margin-top:3px;" class="py-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                    </svg>
                                    <span class="ml-2 hidden">Refresh</span>
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</template>
