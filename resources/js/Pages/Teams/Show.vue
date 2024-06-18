<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteTeamForm from '@/Pages/Teams/Partials/DeleteTeamForm.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import TeamMemberManager from '@/Pages/Teams/Partials/TeamMemberManager.vue';
import UpdateTeamNameForm from '@/Pages/Teams/Partials/UpdateTeamNameForm.vue';
import TeamDropdown from "@/Pages/Teams/Partials/TeamDropdown.vue";

defineProps({
    team: Object,
    availableRoles: Array,
    permissions: Object,
});
</script>

<template>
    <AppLayout title="Team Settings">
        <template #header>
            {{ team.name }}
        </template>

        <div>
            <div v-if="$page.props.auth.user.all_teams.length > 1" class="mb-10">
                <div class="flex items-center">
                    <div class="mr-2 font-medium">Team:</div>
                    <TeamDropdown />
                </div>
            </div>

            <UpdateTeamNameForm :team="team" :permissions="permissions" />

            <TeamMemberManager
                class="mt-10 sm:mt-0"
                :team="team"
                :available-roles="availableRoles"
                :user-permissions="permissions"
            />

            <template v-if="permissions.canDeleteTeam && ! team.personal_team">
                <SectionBorder />

                <DeleteTeamForm class="mt-10 sm:mt-0" :team="team" />
            </template>
        </div>
    </AppLayout>
</template>
