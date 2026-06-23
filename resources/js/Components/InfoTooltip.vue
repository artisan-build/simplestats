<script setup>
import {computed} from 'vue';

const props = defineProps({
    // Spelled-out name, shown as the green title.
    title: {type: String, default: ''},
    // Description shown below the title.
    text: {type: String, default: ''},
    // Pre-built inner HTML (e.g. a chart's multi-series info); overrides title/text.
    html: {type: String, default: ''},
    placement: {type: String, default: 'top'},
    iconClass: {type: String, default: 'size-5'},
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
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" :class="iconClass">
            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
        </svg>
    </span>
</template>
