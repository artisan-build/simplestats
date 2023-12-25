<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import CreateTeamForm from '@/Pages/Teams/Partials/CreateTeamForm.vue';
</script>

<template>
    <AppLayout title="Create Team">
        <template #header>
            Create Team
        </template>

        <div>
            <CreateTeamForm />
        </div>
    </AppLayout>
</template>
