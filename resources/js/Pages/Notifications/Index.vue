<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import FormSection from "@/Components/FormSection.vue";
import SelectInput from "@/Components/SelectInput.vue";
import Checkbox from "@/Components/Checkbox.vue";
import {router, usePage} from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import PaperAirplaneIcon from 'bootstrap-icons/icons/send-fill.svg?component';
import {capitalize} from "@/Helpers/StringHelpers.js";
import {computed, ref} from "vue";

const page = usePage();

const props = defineProps({
    notifications: Object,
    notificationTypes: Array,
    notificationStatsTypes: Array,
    notificationFrequencies: Array,
    loginSummaryEnabled: Boolean,
})

const loginSummaryChecked = ref(props.loginSummaryEnabled);

const updateLoginSummary = () => {
    router.post(route('user-settings.login-summary'), {enabled: loginSummaryChecked.value}, {
        preserveScroll: true,
    });
}

const hasConfigurableOptions = (notification) =>
    Object.keys(notification.settings?.tracking ?? {}).length > 0 || !!notification.frequency;

const visibleNotifications = computed(() => {
    const result = {};
    for (const [group, list] of Object.entries(props.notifications ?? {})) {
        const filtered = list.filter(hasConfigurableOptions);
        if (filtered.length > 0) {
            result[group] = filtered;
        }
    }
    return result;
});

const update = (notification) => {
    router.patch(route("notifications.update", notification), notification, {
        preserveScroll: true,
    });
}

const trigger = (notification) => {
    router.post(route("notifications.trigger", notification), notification);
}
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
            <FormSection>
                <template #form>
                    <div class="col-span-6">
                        <div>
                            <h3>Login Summary</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-dark-400 text-justify">
                                Shows a small summary across all your projects when you return after being away for a while.
                            </p>
                        </div>
                        <div class="w-full mt-3 px-3 py-2 gap-3 border rounded-md flex flex-col md:flex-row justify-between items-start md:items-center bg-gray-50 dark:bg-dark-900">
                            <div class="truncate">
                                All projects
                            </div>
                            <label class="flex items-center">
                                <Checkbox
                                    name="loginSummaryEnabled"
                                    @change="updateLoginSummary"
                                    v-model:checked="loginSummaryChecked"
                                />
                                <span class="ms-2 text-sm text-gray-600 dark:text-dark-400">
                                    Enabled
                                </span>
                            </label>
                        </div>
                    </div>
                    <div v-for="(groupNotifications, group) in visibleNotifications" class="col-span-6">
                        <div>
                            <h3>{{ notificationTypes[group]?.title ?? group }}</h3>
                            <p v-if="notificationTypes[group]?.description" class="mt-1 text-sm text-gray-600 dark:text-dark-400 text-justify">
                                {{ notificationTypes[group]?.description }}
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
                                    <div v-if="notification.settings['types']">
                                        <SelectInput
                                            v-model="notification.settings['types']"
                                            class="w-32 dark:bg-dark-950 !py-1"
                                            :options="notificationStatsTypes"
                                            :multiple="true"
                                            @change="() => update(notification)"
                                        />
                                    </div>
                                    <div v-if="notification.frequency">
                                        <SelectInput
                                            v-model="notification.frequency"
                                            name="frequency"
                                            class="w-32 dark:bg-dark-950 !py-1"
                                            :options="notificationFrequencies"
                                            @change="() => update(notification)"
                                        />
                                    </div>
                                    <div v-if="notification.frequency">
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
        </div>
    </AppLayout>
</template>

<style scoped>

</style>
