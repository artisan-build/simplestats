const POSITIVE = 'text-green-600 dark:text-green-400';
const NEGATIVE = 'text-red-600 dark:text-red-400';

export const formatRoas = (value) => value === null ? '–' : `${value.toFixed(2)}×`;

export function roasClass(value, neutral = '') {
    if (value === null) {
        return neutral;
    }

    return value >= 1 ? POSITIVE : NEGATIVE;
}

export function profitClass(value, neutral = '') {
    if (value === 0) {
        return neutral;
    }

    return value > 0 ? POSITIVE : NEGATIVE;
}
