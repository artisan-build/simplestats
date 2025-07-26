<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ConnectedAccountsForm from '@/Pages/Profile/Partials/ConnectedAccountsForm.vue';
import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm.vue';
import LogoutOtherBrowserSessionsForm from '@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import SetPasswordForm from '@/Pages/Profile/Partials/SetPasswordForm.vue';
import TwoFactorAuthenticationForm from '@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue';
import AppearanceSettingsForm from "@/Pages/Profile/Partials/AppearanceSettingsForm.vue";

defineProps({
    confirmsTwoFactorAuthentication: Boolean,
    sessions: Array,
});

const appEnv = import.meta.env.VITE_APP_ENV;
</script>

<template>
    <AppLayout title="Profile">
        <template #header>
            Profile
        </template>

        <div>

            <div v-if="$page.props.jetstream.canUpdateProfileInformation">
                <UpdateProfileInformationForm :user="$page.props.auth.user" />

                <SectionBorder />
            </div>

            <div>
                <AppearanceSettingsForm />

                <SectionBorder />
            </div>

            <div v-if="$page.props.jetstream.canUpdatePassword">
                <div v-if="$page.props.socialstream.hasPassword">
                    <UpdatePasswordForm class="mt-10 sm:mt-0" />

                    <SectionBorder />
                </div>
                <div v-else>
                    <SetPasswordForm class="mt-10 sm:mt-0" />

                    <SectionBorder />
                </div>
            </div>

            <div v-if="$page.props.jetstream.canManageTwoFactorAuthentication">
                <TwoFactorAuthenticationForm
                    :requires-confirmation="confirmsTwoFactorAuthentication"
                    class="mt-10 sm:mt-0"
                />

                <SectionBorder />
            </div>

            <div v-if="$page.props.socialstream.providers.length && $page.props.socialstream.show">
                <ConnectedAccountsForm class="mt-10 sm:mt-0"/>

                <SectionBorder/>
            </div>

            <div v-if="$page.props.socialstream.hasPassword && appEnv !== 'demo'">
                <LogoutOtherBrowserSessionsForm :sessions="sessions" class="mt-10 sm:mt-0" />

                <SectionBorder/>
            </div>

            <template
                v-if="$page.props.jetstream.hasAccountDeletionFeatures && $page.props.socialstream.hasPassword">
                <DeleteUserForm class="mt-10 sm:mt-0"/>
            </template>
        </div>
    </AppLayout>
</template>
