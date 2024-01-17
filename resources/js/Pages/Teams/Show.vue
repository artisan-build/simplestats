<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteTeamForm from '@/Pages/Teams/Partials/DeleteTeamForm.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import TeamMemberManager from '@/Pages/Teams/Partials/TeamMemberManager.vue';
import UpdateTeamNameForm from '@/Pages/Teams/Partials/UpdateTeamNameForm.vue';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

defineProps({
    team: Object,
    availableRoles: Array,
    permissions: Object,
});
</script>

<template>
    <AppLayout title="Team Settings">
        <template #header>
            <div>
                Team: {{ team.name }}
            </div>
            <div v-if="$page.props.jetstream.canCreateTeams">
                <SecondaryButton type="button" @click="$inertia.visit(route('teams.create'))">Create New Team</SecondaryButton>
            </div>
        </template>

        <div>
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
