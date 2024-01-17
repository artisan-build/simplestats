<script setup>

defineProps({
    stats: Object,
    isTotal: Boolean,
});

</script>

<template>
    <td class="p-3 text-left">
        <svg v-if="isTotal" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="inline-block w-5 h-5 mr-1">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.737 5.1a3.375 3.375 0 0 1 2.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 0 1 .9 2.7m0 0a3 3 0 0 1-3 3m0 3h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Zm-3 6h.008v.008h-.008v-.008Zm0-6h.008v.008h-.008v-.008Z" />
        </svg>
        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="inline-block w-5 h-5 mr-1">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 17.25v-.228a4.5 4.5 0 0 0-.12-1.03l-2.268-9.64a3.375 3.375 0 0 0-3.285-2.602H7.923a3.375 3.375 0 0 0-3.285 2.602l-2.268 9.64a4.5 4.5 0 0 0-.12 1.03v.228m19.5 0a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3m19.5 0a3 3 0 0 0-3-3H5.25a3 3 0 0 0-3 3m16.5 0h.008v.008h-.008v-.008Zm-3 0h.008v.008h-.008v-.008Z" />
        </svg>
        {{ stats.name }}
    </td>
    <td class="w-1 bg-white border-y border-white"></td>
    <td>{{ stats.lifetime.REG }}</td>
    <td>{{ stats.lifetime.REG2DAU }}</td>
    <td>{{ stats.lifetime.PU }}</td>
    <td>{{ stats.lifetime.REG2PU }}</td>
    <td>{{ stats.lifetime.TAU }}</td>
    <td>{{ stats.lifetime.REG2TAU }}</td>
    <td>{{ stats.lifetime.ARPU }}</td>
    <td class="pr-2">{{ stats.lifetime.ARPPU }}</td>
    <td class="w-1 bg-white border-y border-white"></td>
    <td>{{ stats.yesterday.REG }}</td>
    <td>{{ stats.yesterday.NAU }}</td>
    <td>{{ stats.yesterday.DAU }}</td>
    <td>{{ stats.yesterday.DAPU }}</td>
    <td>{{ stats.yesterday.DAAPU }}</td>
    <td>{{ stats.yesterday.WAU }}</td>
    <td>{{ stats.yesterday.MAU }}</td>
    <td>{{ stats.yesterday.MAPU }}</td>
    <td>{{ stats.yesterday.MAAPU }}</td>
    <td class="pr-2">{{ stats.yesterday.REV }}</td>
</template>

<style scoped>

</style>
