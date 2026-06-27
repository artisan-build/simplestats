<script setup>
import {router, usePage} from "@inertiajs/vue3";
import {computed} from "vue";
import Dropdown from "@/Components/Dropdown.vue";
import InputLabel from "@/Components/InputLabel.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import EyeIcon from 'bootstrap-icons/icons/eye.svg?component';
import CheckIcon from 'bootstrap-icons/icons/check2.svg?component';

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
                <SecondaryButton aria-label="Dashboard detail level" style="margin-top:3px;" class="py-3">
                    <EyeIcon class="size-4" />
                </SecondaryButton>
            </template>
            <template #content>
                <div class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-dark-400">
                    Dashboard details
                </div>
                <button
                    v-for="preset in presets"
                    :key="preset.id"
                    type="button"
                    class="flex w-full items-center justify-between gap-2 px-4 py-2 text-sm text-gray-700 dark:text-dark-200 hover:bg-gray-100 dark:hover:bg-dark-600"
                    :class="{'font-semibold text-gray-900 dark:text-white': currentPreset === preset.id}"
                    @click="setPreset(preset.id)"
                >
                    {{ preset.name }}
                    <CheckIcon v-if="currentPreset === preset.id" class="size-4" />
                </button>
            </template>
        </Dropdown>
    </div>
</template>
