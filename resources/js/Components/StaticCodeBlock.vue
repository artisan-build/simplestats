<script setup>
defineProps({
    title: String,
    html: {
        type: String,
        default: null,
    },
    code: {
        type: String,
        default: null,
    },
})
</script>

<template>
    <div style="background-color:#24292e;" class="text-left rounded-2xl p-6 w-full flex flex-col shadow-xl">
        <div class="flex flew-row items-center gap-2">
            <div class="rounded-full w-3 h-3 bg-[#ed6a5d]"></div>
            <div class="rounded-full w-3 h-3 bg-[#f5c04f]"></div>
            <div class="rounded-full w-3 h-3 bg-[#62c555]"></div>
            <p class="flex-1 text-center text-white opacity-80 text-xs self-end -translate-x-6">
                {{ title }}
            </p>
        </div>
        <div class="text-sm mt-4">
            <div v-if="html" class="overflow-auto" v-html="html"></div>
            <pre v-else class="overflow-auto text-[#e1e4e8] font-mono leading-relaxed"><code>{{ code }}</code></pre>
        </div>
    </div>
</template>
