<script setup>

defineProps({
    stats: Object,
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
    <td>{{ stats.per_date.GROSS.toFixed(2).toString().concat(' EUR') }}</td>
    <td>{{ stats.per_date.NET.toFixed(2).toString().concat(' EUR') }}</td>
    <td>{{ stats.per_date.FEE.toFixed(2).toString().concat(' EUR') }}</td>
    <td class="pr-2">{{ stats.per_date.ARPDAU.toFixed(2).toString().concat(' EUR') }}</td>
    <td class="w-1 bg-white border-y border-white"></td>
    <td>{{ stats.lifetime?.REG ?? '-' }}</td>
    <td>{{ stats.lifetime?.REG2DAU.toFixed(2).toString().concat('%') ?? '-' }}</td>
    <td>{{ stats.lifetime?.PU ?? '-' }}</td>
    <td>{{ stats.lifetime?.REG2PU.toFixed(2).toString().concat('%') ?? '-' }}</td>
    <td>{{ stats.lifetime?.TAU ?? '-' }}</td>
    <td>{{ stats.lifetime?.REG2TAU.toFixed(2).toString().concat('%') ?? '-' }}</td>
    <td>{{ stats.lifetime?.ARPU.toFixed(2).toString().concat(' EUR') ?? '-' }}</td>
    <td>{{ stats.lifetime?.ARPPU.toFixed(2).toString().concat(' EUR') ?? '-' }}</td>
    <td class="pr-2">{{ stats.lifetime?.NET.toFixed(2).toString().concat(' EUR') ?? '-' }}</td>
</template>
