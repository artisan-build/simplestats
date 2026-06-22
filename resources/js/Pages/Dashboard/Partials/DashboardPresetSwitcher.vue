<script setup>
import {router, usePage} from "@inertiajs/vue3";
import {computed} from "vue";
import Dropdown from "@/Components/Dropdown.vue";
import InputLabel from "@/Components/InputLabel.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const props = defineProps({
    currentProject: Object,
    trackingUsageBits: Object,
});

const page = usePage();

const presets = [
    {id: 'simple', name: 'Simple'},
    {id: 'advanced', name: 'Advanced'},
    {id: 'expert', name: 'Expert'},
];

// The switcher only matters when the project tracks everything; with fewer bits
// the presets collapse to nearly the same set, so there is little to switch.
const fullTracking = computed(() => {
    const maxBits = Object.values(props.trackingUsageBits).reduce((a, b) => a + b);

    return maxBits === props.currentProject.usage;
});

const currentPreset = computed(() => page.props.auth.user.settings.dashboardPreset ?? 'advanced');

const setPreset = (preset) => {
    // preserveState: false forces StatsCharts to re-run setup with the new preset;
    // a POST defaults to preserving state, which would leave the charts stale.
    router.post(route('user-settings.set-dashboard-preset'), {preset}, {preserveScroll: true, preserveState: false});
};
</script>

<template>
    <div v-if="fullTracking" data-tour="advanced-toggle">
        <InputLabel class="hidden" for="dashboardPreset" value="&nbsp;" />
        <Dropdown align="right" width="48">
            <template #trigger>
                <SecondaryButton title="Dashboard detail level" style="margin-top:3px;" class="py-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                </SecondaryButton>
            </template>
            <template #content>
                <button
                    v-for="preset in presets"
                    :key="preset.id"
                    type="button"
                    class="flex w-full items-center justify-between gap-2 px-4 py-2 text-sm text-gray-700 dark:text-dark-200 hover:bg-gray-100 dark:hover:bg-dark-600"
                    :class="{'font-semibold text-gray-900 dark:text-white': currentPreset === preset.id}"
                    @click="setPreset(preset.id)"
                >
                    {{ preset.name }}
                    <svg v-if="currentPreset === preset.id" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                    </svg>
                </button>
            </template>
        </Dropdown>
    </div>
</template>
