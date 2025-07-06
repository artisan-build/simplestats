<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import ProfileDropdown from "@/Pages/Profile/Partials/ProfileDropdown.vue";
import FormSection from "@/Components/FormSection.vue";
import SectionBorder from "@/Components/SectionBorder.vue";
import SelectInput from "@/Components/SelectInput.vue";
import Checkbox from "@/Components/Checkbox.vue";
import {capitalize} from "lodash";
import {router} from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";

defineProps({
    notifications: Object,
    notificationFrequencies: Array,
})

const update = (groupNotification) => {
    router.patch(route("notifications.update", groupNotification), groupNotification);
}

const group2Name = {
    "App\\Models\\Project": {
        "title": "Project Reporting",
        "description": "Choose which stats to include and how often you’d like to receive the report – or send a report manually anytime.",
    },
}
</script>

<template>
    <AppLayout title="Notifications">
        <template #header>
            <div>Notifications</div>
            <ProfileDropdown />
        </template>
        <div class="mt-3">
            <div v-for="(team, index) in $page.props.auth.user.all_teams">
                <FormSection>
                    <template #title>
                        {{ team.name }}
                    </template>

                    <template #description>
                        Customize your settings to receive only the notifications that matter to you. These changes won't affect your team members.
                    </template>

                    <template #form>
                        <div v-for="(groupNotifications, group) in notifications[team.id]" class="col-span-6">
                            <div>
                                <h3>{{ group2Name[group]?.title ?? group }}</h3>
                                <p v-if="group2Name[group]?.description" class="mt-1 text-sm text-gray-600 dark:text-gray-400 text-justify">
                                    {{ group2Name[group]?.description }}
                                </p>
                            </div>
                            <div
                                v-for="groupNotification in groupNotifications"
                                class="w-full mt-3 px-3 py-2 gap-3 border rounded-md flex flex-col lg:flex-row justify-between items-start lg:items-center bg-gray-50 dark:bg-dark-900"
                            >
                                <div>
                                    {{ groupNotification.notifiable.name }}
                                </div>
                                <div class="flex gap-3 flex-col sm:flex-row">
                                    <div class="flex items-center gap-3">
                                        <div v-for="(enabled, setting) in groupNotification.settings">
                                            <label class="flex items-center">
                                                <Checkbox
                                                    :name="setting"
                                                    :disabled="groupNotification.frequency === 'disabled'"
                                                    @change="() => update(groupNotification)"
                                                    v-model:checked="groupNotification.settings[setting]"
                                                />
                                                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">
                                                    {{ capitalize(setting) }}
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div>
                                            <SelectInput
                                                v-model="groupNotification.frequency"
                                                name="frequency"
                                                class="py-1 w-32 dark:bg-dark-950"
                                                :options="notificationFrequencies"
                                                @change="() => update(groupNotification)"
                                            />
                                        </div>
                                        <div>
                                            <PrimaryButton type="button">Send Now</PrimaryButton>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </FormSection>

                <SectionBorder v-if="index < $page.props.auth.user.all_teams.length-1" />
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>

</style>
