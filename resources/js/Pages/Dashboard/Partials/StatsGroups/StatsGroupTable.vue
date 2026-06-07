<script setup>
import {computed, ref, toRef} from 'vue';
import {formatCurrency, formatNumber, formatPercent} from "@/Helpers/NumberHelpers.js";
import {usePage} from "@inertiajs/vue3";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import {useStatsComparison} from "@/Composables/useStatsComparison.js";

/**
 * Generic grouped stats table: sortable header, gradient bars, comparison
 * values and the "Load more" footer. The consumers (StatsGroup,
 * StatsCustomPropertyGroup) only provide the column config, the tabs and
 * the content of the name cell.
 */
const props = defineProps({
    title: String,
    loading: Boolean,
    data: Array,
    dataPrevious: Array,
    // [{key, label, labelSmall?, format: 'number'|'percent'|'currency', width, visible?}]
    columns: Array,
    sort: String,
    nameLabel: String,
    nameWidth: String,
    // (item) => 0-100, drives the gradient bar of the name cell
    percentage: Function,
    emptyText: String,
    canLoadMore: Boolean,
});

const emit = defineEmits(['sort', 'load-more']);

const page = usePage();

const {comparisonChange, comparisonClass, formatComparison} = useStatsComparison(toRef(props, 'dataPrevious'));

const visibleColumns = computed(() => props.columns.filter((column) => column.visible !== false));

const formatters = {
    number: (value) => formatNumber(Number(value)),
    percent: (value) => formatPercent(Number(value)),
    currency: (value) => formatCurrency(value, {}, page.props.teamCurrency),
};

const formatValue = (column, item) => formatters[column.format](item[column.key]);

const isDarkMode = ref(document.documentElement.classList.contains('dark'));
const gradientColor = (isDarkMode.value) ? '46, 89, 113' : '220, 236, 254';
</script>

<template>
    <div class="@container flex-grow min-h-96 overflow-x-auto">
        <div v-if="loading" class="flex items-center justify-center h-96">
            <div>
                <svg class="text-gray-300 animate-spin -ml-1 mr-3 h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>
        <div v-else class="p-4 text-sm @md:text-base">
            <div class="flex flex-col @lg:flex-row justify-between mb-4">
                <div class="font-bold text-center mb-3 @lg:mb-0">
                    {{ title }}
                </div>
                <slot name="tabs" />
            </div>
            <div class="overflow-y-auto" style="max-height: 300px;">
                <table class="w-full table-fixed">
                    <tbody>
                        <tr class="sticky bg-white dark:bg-dark-800 top-0 font-medium text-gray-600 dark:text-dark-300">
                            <td v-for="column in [{key: 'name', label: nameLabel, width: nameWidth}, ...visibleColumns]" :key="column.key"
                                :style="`width: ${column.width};`" :class="column.key === 'name' ? 'py-1.5' : 'p-1.5 text-right'"
                            >
                                <button class="whitespace-nowrap" :class="sort === column.key ? 'text-indigo-600' : ''" @click="emit('sort', column.key)">
                                    <span v-if="column.labelSmall" class="inline-block @2xl:hidden">{{ column.labelSmall }}</span>
                                    <span :class="column.labelSmall ? 'hidden @2xl:inline-block' : ''">{{ column.label }}</span>
                                    <svg v-if="sort === column.key" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 hidden @md:inline-block">
                                        <path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        <tr v-for="item in data" :key="item.type_id ?? item.name">
                            <td class="py-1.5">
                                <div class="flex items-center h-9 w-full px-2 py-1.5 rounded-l"
                                     :style="'background: linear-gradient(to right, rgba('+gradientColor+', 1) '+percentage(item)+'%, rgba('+gradientColor+', 0) '+percentage(item)+'%);'"
                                >
                                    <slot name="name-cell" :item="item" />
                                </div>
                            </td>
                            <td v-for="column in visibleColumns" :key="column.key" class="p-1.5 text-right">
                                <span>{{ formatValue(column, item) }}</span>
                                <span v-if="comparisonChange(item, column.key) !== null" class="block text-xs font-semibold leading-tight" :class="comparisonClass(comparisonChange(item, column.key))">{{ formatComparison(comparisonChange(item, column.key)) }}</span>
                            </td>
                        </tr>
                        <tr v-if="! data.length && emptyText">
                            <td :colspan="visibleColumns.length + 1" class="py-6 text-center text-gray-400">{{ emptyText }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="canLoadMore" class="flex justify-center mt-2">
                <SecondaryButton @click="emit('load-more')">
                    Load more
                </SecondaryButton>
            </div>
        </div>
    </div>
</template>
