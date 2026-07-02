import {computed} from 'vue';

/**
 * Shared "compare to previous period" helpers for the grouped stats tables.
 * Expects a ref holding the previous period rows (matched by type_id).
 */
export function useStatsComparison(dataPrevious) {
    const previousByTypeId = computed(() => {
        const map = {};
        for (const item of dataPrevious.value) {
            if (item.type_id !== undefined && item.type_id !== null) {
                map[item.type_id] = item;
            }
        }
        return map;
    });

    const comparisonChange = (item, field) => {
        if (item.type_id === undefined || item.type_id === null) {
            return null;
        }

        const previous = previousByTypeId.value[item.type_id] ?? null;
        if (! previous) {
            return null;
        }

        // null = not computable (e.g. censored retention checkpoints); a delta
        // against a coalesced 0 would fake a -100% on a young cohort
        if (item[field] === null || previous[field] === null) {
            return null;
        }

        const current = Number(item[field] ?? 0);
        const prev = Number(previous[field] ?? 0);

        if (prev === 0 && current === 0) {
            return null;
        }
        if (prev === 0) {
            return 100;
        }

        return ((current - prev) / prev) * 100;
    };

    const comparisonClass = (change) => {
        if (change === null) {
            return '';
        }
        if (change > 0) {
            return 'text-green-600 dark:text-green-400';
        }
        if (change < 0) {
            return 'text-red-600 dark:text-red-400';
        }
        return 'text-gray-400';
    };

    const formatComparison = (change) => {
        if (change === null) {
            return '';
        }
        const decimals = Math.abs(change) >= 10 ? 0 : 1;
        const rounded = Number(change.toFixed(decimals));
        const prefix = rounded > 0 ? '+' : '';
        return `${prefix}${rounded}%`;
    };

    return {comparisonChange, comparisonClass, formatComparison};
}
