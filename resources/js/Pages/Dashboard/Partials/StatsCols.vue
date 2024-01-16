<script setup>

defineProps({
    stats: Object,
});

</script>

<template>
    <td class="p-3 text-left">{{ stats.name }}</td>
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
