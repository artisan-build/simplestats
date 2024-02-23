<script setup>
import {formatNumber, formatCurrency, formatPercent} from "@/Helpers/NumberHelpers.js";

defineProps({
    stats: Object,
});

</script>

<template>
    <td class="p-3 text-left">{{ stats.date }}</td>
    <td class="w-1 bg-white border-y border-white"></td>
    <td>{{ formatNumber(stats.per_date.REG) }}</td>
    <td>{{ formatNumber(stats.per_date.NAU) }}</td>
    <td>{{ formatNumber(stats.per_date.NAPU) }}</td>
    <td>{{ formatNumber(stats.per_date.DAU) }}</td>
    <td>{{ formatNumber(stats.per_date.DAPU) }}</td>
    <td class="pr-2">{{ formatNumber(stats.per_date.DAAPU) }}</td>
    <td class="w-1 bg-white border-y border-white"></td>
    <td>{{ formatCurrency(stats.per_date.GROSS, $page.props.teamCurrency) }}</td>
    <td>{{ formatCurrency(stats.per_date.NET, $page.props.teamCurrency) }}</td>
    <td>{{ formatCurrency(stats.per_date.FEE, $page.props.teamCurrency) }}</td>
    <td class="pr-2">{{ formatCurrency(stats.per_date.ARPDAU, $page.props.teamCurrency) }}</td>
    <td class="w-1 bg-white border-y border-white"></td>
    <td>{{ formatNumber(stats.lifetime?.REG) }}</td>
    <td>{{ formatPercent(stats.lifetime?.REG2DAU) }}</td>
    <td>{{ formatNumber(stats.lifetime?.PU) }}</td>
    <td>{{ formatPercent(stats.lifetime?.REG2PU) }}</td>
    <td>{{ formatNumber(stats.lifetime?.TAU) }}</td>
    <td>{{ formatPercent(stats.lifetime?.REG2TAU) }}</td>
    <td>{{ formatCurrency(stats.lifetime?.ARPU, $page.props.teamCurrency) }}</td>
    <td>{{ formatCurrency(stats.lifetime?.ARPPU, $page.props.teamCurrency) }}</td>
    <td class="pr-2">{{ formatCurrency(stats.lifetime?.NET, $page.props.teamCurrency) }}</td>
</template>
