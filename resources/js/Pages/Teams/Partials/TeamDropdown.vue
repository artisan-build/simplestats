<script setup>

import DropdownLink from "@/Components/DropdownLink.vue";
import Dropdown from "@/Components/Dropdown.vue";
import {router} from "@inertiajs/vue3";
import ChevronExpandIcon from "bootstrap-icons/icons/chevron-expand.svg?component";
import CheckCircleIcon from "bootstrap-icons/icons/check-circle.svg?component";

const emit = defineEmits(['switched-team']);

const props = defineProps({
    align: {
        default: 'right',
        type: String,
    },
});

const switchToTeam = (team) => {
    router.visit(route('current-team.update'), {
        method: 'put',
        data: {team_id: team.uuid},
        onFinish: () => emit('switched-team', team),
    });
};

const showMangeTeams = true;
const highligtTeam = true;//route().current().includes('teams') || (route().current().includes('projects'));
</script>

<template>
    <Dropdown v-if="$page.props.jetstream.hasTeamFeatures" :align="align" width="60">
    <template #trigger>
            <span class="inline-flex rounded-md">
                <button type="button"
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-dark-400 dark:bg-dark-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150"
                        :class="{ 'text-indigo-600 bg-gray-100' : highligtTeam, 'bg-white' : ! highligtTeam }"
                >
                    {{ $page.props.auth.user.current_team.name }}

                    <ChevronExpandIcon class="ms-2 -me-0.5 size-4" />
                </button>
            </span>
        </template>

        <template #content>
            <div class="w-60">
                <div v-if="showMangeTeams && $page.props.jetstream.canCreateTeams" class="border-b border-gray-200 dark:border-dark-600">
                    <!-- Team Management -->
                    <div class="block px-4 py-2 text-xs text-gray-400">
                        Manage Team
                    </div>

                    <DropdownLink :href="route('teams.create')">
                        Create New Team
                    </DropdownLink>
                </div>

                <!-- Team Switcher -->
                <template v-if="$page.props.auth.user.all_teams.length > 0">
                    <div v-if="showMangeTeams" />

                    <div class="block px-4 py-2 text-xs text-gray-400">
                        Switch Teams
                    </div>

                    <template v-for="team in $page.props.auth.user.all_teams" :key="team.uuid">
                        <form @submit.prevent="switchToTeam(team)">
                            <DropdownLink as="button">
                                <div class="flex items-center">
                                    <CheckCircleIcon v-if="team.uuid == $page.props.auth.user.current_team_uuid" class="me-2 size-5 text-green-400" />

                                    <div>{{ team.name }}</div>
                                </div>
                            </DropdownLink>
                        </form>
                    </template>
                </template>
            </div>
        </template>
    </Dropdown>
</template>
