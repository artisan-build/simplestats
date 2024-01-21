<script setup>

defineProps({
    stats: Object,
    isTotal: Boolean,
});

</script>

<template>
    <td class="p-3 text-left">{{ stats.date }}</td>
    <td class="w-1 bg-white border-y border-white"></td>
    <td>{{ stats.per_date.REG }}</td>
    <td>{{ stats.per_date.NAU }}</td>
    <td>{{ stats.per_date.NAPU }}</td>
    <td>{{ stats.per_date.DAU }}</td>
    <td>{{ stats.per_date.DAPU }}</td>
    <td class="pr-2">{{ stats.per_date.DAAPU }}</td>
    <td class="w-1 bg-white border-y border-white"></td>
    <td>{{ stats.per_date.GROSS }} EUR</td>
    <td>{{ stats.per_date.NET }} EUR</td>
    <td>{{ stats.per_date.FEE }} EUR</td>
    <td class="pr-2">{{ stats.per_date.ARPDAU }} EUR</td>
    <td class="w-1 bg-white border-y border-white"></td>
    <td>{{ stats.lifetime.REG }}</td>
    <td>{{ stats.lifetime.REG2DAU }}%</td>
    <td>{{ stats.lifetime.PU }}</td>
    <td>{{ stats.lifetime.REG2PU }}%</td>
    <td>{{ stats.lifetime.TAU }}</td>
    <td>{{ stats.lifetime.REG2TAU }}%</td>
    <td>{{ stats.lifetime.ARPU }} EUR</td>
    <td>{{ stats.lifetime.ARPPU }} EUR</td>
    <td class="pr-2">{{ stats.lifetime.NET }} EUR</td>
</template>
