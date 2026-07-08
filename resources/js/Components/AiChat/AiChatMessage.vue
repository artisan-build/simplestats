<script setup>
import {computed} from 'vue';
import {renderSimpleMarkdown} from '@/Helpers/simpleMarkdown.js';

const props = defineProps({
    message: Object,
});

const renderedContent = computed(() => renderSimpleMarkdown(props.message.content));
</script>

<template>
    <div v-if="message.role === 'user'" class="flex justify-end">
        <div class="max-w-[85%] rounded-lg rounded-br-sm bg-indigo-100 dark:bg-indigo-500 px-3 py-2 text-sm text-gray-900 dark:text-white whitespace-pre-wrap break-words">{{ message.content }}</div>
    </div>
    <div v-else class="flex">
        <div class="max-w-full text-sm text-gray-700 dark:text-gray-200 space-y-2 break-words" v-html="renderedContent"></div>
    </div>
</template>
