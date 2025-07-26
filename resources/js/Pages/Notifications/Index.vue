<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import FormSection from "@/Components/FormSection.vue";
import SectionBorder from "@/Components/SectionBorder.vue";
import SelectInput from "@/Components/SelectInput.vue";
import Checkbox from "@/Components/Checkbox.vue";
import {router, usePage} from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {PaperAirplaneIcon} from '@heroicons/vue/24/solid';
import {capitalize} from "lodash";

const page = usePage();

const props = defineProps({
    notifications: Object,
    notificationTypes: Array,
    notificationFrequencies: Array,
})

const update = (notification) => {
    router.patch(route("notifications.update", notification), notification);
}

const trigger = (notification) => {
    router.post(route("notifications.trigger", notification), notification);
}

const group2Name = {
    "App\\Models\\Project": {
        "title": "Project Reporting",
        "description": "Choose which stats to include and how often you’d like to receive the report – or send a report manually anytime.",
    },
}

const sortedTeams = page.props.auth.user.all_teams.sort((a, b) => ('membership' in a ? 1 : 0) - ('membership' in b ? 1 : 0));
</script>

<template>
    <AppLayout title="Notifications">
        <template #header>
            Notifications
        </template>
        <div>
            <div class="max-w-3xl mx-auto mb-4">
                Customize your notification settings to receive only the notifications that matter to you.
            </div>
            <div v-for="(team, index) in sortedTeams">
                <FormSection>
                    <template #title>
                        {{ team.name }}
                    </template>

                    <template #form>
                        <div v-for="(groupNotifications, group) in notifications[team.id]" class="col-span-6">
                            <div>
                                <h3>{{ group2Name[group]?.title ?? group }}</h3>
                                <p v-if="group2Name[group]?.description" class="mt-1 text-sm text-gray-600 dark:text-dark-400 text-justify">
                                    {{ group2Name[group]?.description }}
                                </p>
                            </div>
                            <div
                                v-for="notification in groupNotifications"
                                class="w-full mt-3 px-3 py-2 gap-3 border rounded-md flex flex-col md:flex-row justify-between items-start md:items-center bg-gray-50 dark:bg-dark-900"
                            >
                                <div class="truncate">
                                    {{ notification.notifiable.name }}
                                </div>
                                <div class="flex gap-3 flex-col sm:flex-row">
                                    <div class="flex items-center gap-3">
                                        <div v-for="(enabled, setting) in notification.settings['tracking']">
                                            <label class="flex items-center">
                                                <Checkbox
                                                    :name="setting"
                                                    :disabled="notification.frequency === 'disabled'"
                                                    @change="() => update(notification)"
                                                    v-model:checked="notification.settings['tracking'][setting]"
                                                />
                                                <span class="ms-2 text-sm text-gray-600 dark:text-dark-400">
                                                    {{ capitalize(setting) }}
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div>
                                            <SelectInput
                                                v-model="notification.settings['types']"
                                                class="w-32 dark:bg-dark-950 !py-1"
                                                :options="notificationTypes"
                                                :multiple="true"
                                                @change="() => update(notification)"
                                            />
                                        </div>
                                        <div>
                                            <SelectInput
                                                v-model="notification.frequency"
                                                name="frequency"
                                                class="w-32 dark:bg-dark-950 !py-1"
                                                :options="notificationFrequencies"
                                                @change="() => update(notification)"
                                            />
                                        </div>
                                        <div>
                                            <PrimaryButton v-tooltip="'Send Now'" type="button" @click="trigger(notification)">
                                                <PaperAirplaneIcon class="size-4" />
                                            </PrimaryButton>
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
