<script setup>
import {formatNumber, formatCurrency, formatPercent, formatDate} from "@/Helpers/NumberHelpers.js";

defineProps({
    stats: Object,
    rangeType: String,
});
</script>

<template>
    <td class="border-r border-gray-100 p-3 text-left">{{ (stats.date === 'Total') ? stats.date : formatDate(stats.date, rangeType) }}</td>
    <td class="w-1 bg-white border-y border-white"></td>
    <td class="border-r border-gray-100 pr-1">{{ formatNumber(stats.per_date.REG) }}</td>
    <td class="border-r border-gray-100 pr-1">{{ formatNumber(stats.per_date.NAU) }}</td>
    <td class="border-r border-gray-100 pr-1">{{ formatNumber(stats.per_date.NAPU) }}</td>
    <td class="border-r border-gray-100 pr-1">{{ formatNumber(stats.per_date.DAU) }}</td>
    <td class="border-r border-gray-100 pr-1">{{ formatNumber(stats.per_date.DAPU) }}</td>
    <td class="border-r border-gray-100 pr-1">{{ formatNumber(stats.per_date.DAAPU) }}</td>
    <td class="w-1 bg-white border-y border-white"></td>
    <td class="border-r border-gray-100 pr-1">{{ formatCurrency(stats.per_date.GROSS, {}, $page.props.teamCurrency) }}</td>
    <td class="border-r border-gray-100 pr-1">{{ formatCurrency(stats.per_date.NET, {}, $page.props.teamCurrency) }}</td>
    <td class="border-r border-gray-100 pr-1">{{ formatCurrency(stats.per_date.FEE, {}, $page.props.teamCurrency) }}</td>
    <td class="border-r border-gray-100 pr-1">{{ formatCurrency(stats.per_date.ARPDAU, {}, $page.props.teamCurrency) }}</td>
    <td class="w-1 bg-white border-y border-white"></td>
    <td class="border-r border-gray-100 pr-1">{{ formatNumber(stats.lifetime?.REG) }}</td>
    <td class="border-r border-gray-100 pr-1">{{ formatPercent(stats.lifetime?.REG2DAU) }}</td>
    <td class="border-r border-gray-100 pr-1">{{ formatNumber(stats.lifetime?.PU) }}</td>
    <td class="border-r border-gray-100 pr-1">{{ formatPercent(stats.lifetime?.REG2PU) }}</td>
    <td class="border-r border-gray-100 pr-1">{{ formatNumber(stats.lifetime?.TAU) }}</td>
    <td class="border-r border-gray-100 pr-1">{{ formatPercent(stats.lifetime?.REG2TAU) }}</td>
    <td class="border-r border-gray-100 pr-1">{{ formatCurrency(stats.lifetime?.ARPU, {}, $page.props.teamCurrency) }}</td>
    <td class="border-r border-gray-100 pr-1">{{ formatCurrency(stats.lifetime?.ARPPU, {}, $page.props.teamCurrency) }}</td>
    <td class="pr-2">{{ formatCurrency(stats.lifetime?.NET, {}, $page.props.teamCurrency) }}</td>
</template>
