<script setup>
import { createHighlighter } from 'shiki';

const props = defineProps({
    lang: String,
    theme: String,
    title: String,
    code: String,
})

const highlighter = await createHighlighter({
    langs: [props.lang],
    themes: [props.theme],
});

const html = highlighter.codeToHtml(props.code, {
    lang: props.lang,
    theme: props.theme,
})
</script>

<template>
    <div :style="'background-color:'+highlighter.getTheme(props.theme).bg+';'" class="text-left rounded-2xl p-6 w-full flex flex-col shadow-xl">
        <div class="flex flew-row items-center gap-2">
            <div class="rounded-full w-3 h-3 bg-[#ed6a5d]"></div>
            <div class="rounded-full w-3 h-3 bg-[#f5c04f]"></div>
            <div class="rounded-full w-3 h-3 bg-[#62c555]"></div>
            <p class="flex-1 text-center text-white opacity-80 text-xs self-end -translate-x-6">
                {{ title }}
            </p>
        </div>
        <div class="text-sm mt-4">
            <div class="overflow-auto" v-html="html"></div>
        </div>
    </div>
</template>
