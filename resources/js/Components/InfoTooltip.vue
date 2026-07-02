<script setup>
import {computed} from 'vue';
import InfoCircleIcon from 'bootstrap-icons/icons/info-circle.svg?component';
import {infoTooltipContent} from '@/Helpers/TooltipHelpers.js';

const props = defineProps({
    // Spelled-out name, shown as the green title.
    title: {type: String, default: ''},
    // Description shown below the title.
    text: {type: String, default: ''},
    // Pre-built inner HTML (e.g. a chart's multi-series info); overrides title/text.
    html: {type: String, default: ''},
    placement: {type: String, default: 'top'},
    iconClass: {type: String, default: 'size-4 ml-1'},
});

const content = computed(() => infoTooltipContent(props));
</script>

<template>
    <span
        v-tooltip="{ content, html: true, placement }"
        class="inline-flex align-middle text-gray-400 cursor-help"
    >
        <InfoCircleIcon :class="iconClass" />
    </span>
</template>
