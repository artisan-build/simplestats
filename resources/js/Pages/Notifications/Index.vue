<script setup>

import AppLayout from "@/Layouts/AppLayout.vue";
import ProfileDropdown from "@/Pages/Profile/Partials/ProfileDropdown.vue";
</script>

<template>
    <AppLayout title="Notifications">
        <template #header>
            <div>Notifications</div>
            <ProfileDropdown />
        </template>
    </AppLayout>
</template>

<style scoped>

</style>
