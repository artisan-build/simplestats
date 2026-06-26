<script setup>
import {computed} from 'vue';
import InfoCircleIcon from 'bootstrap-icons/icons/info-circle.svg?component';

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

const content = computed(() => {
    const inner = props.html
        ? props.html
        : `<div><div class="font-bold text-green-300">${props.title}</div><div>${props.text}</div></div>`;

    return `<div class="flex flex-col space-y-4 max-w-64 p-2 text-sm">${inner}</div>`;
});
</script>

<template>
    <span
        v-tooltip="{ content, html: true, placement }"
        class="inline-flex align-middle text-gray-400 cursor-help"
    >
        <InfoCircleIcon :class="iconClass" />
    </span>
</template>
