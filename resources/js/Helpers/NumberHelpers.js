//set en locale for all, else the formating of the StatsButton and StatsCharts will be too bad
const locale = 'en-US';
// const locale = navigator.language;

export const formatMap = {
    'number': formatNumber,
    'currency': formatCurrency,
    'percent': formatPercent,
    'date': formatDate,
};

export function formatNumber(value, additionalOptions) {
    if (typeof value !== 'number' || isNaN(value)) {
        return '-';
    }

    let options = {
        notation: 'compact',
    };

    if (additionalOptions && typeof additionalOptions === 'object') {
        options = { ...options, ...additionalOptions };
    }

    return new Intl.NumberFormat(locale, options).format(value);
}

export function cent2Currency(value) {
    // convert cents to full eur/usd ...
    return value / 100;
}

export function formatCurrency(value, additionalOptions, currency) {
    if (typeof value !== 'number' || isNaN(value)) {
        return '-';
    }

    value = cent2Currency(value);

    let options = {
        style: "currency",
        currency: currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        notation: 'compact',
    };

    if (additionalOptions && typeof additionalOptions === 'object') {
        options = { ...options, ...additionalOptions };
    }

    return new Intl.NumberFormat(locale, options).format(value);
}

export function formatPercent(value, additionalOptions) {
    if (typeof value !== 'number' || isNaN(value)) {
        return '-';
    }

    let options = {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    };

    if (additionalOptions && typeof additionalOptions === 'object') {
        options = { ...options, ...additionalOptions };
    }

    return new Intl.NumberFormat(locale, options).format(value)+'%';
}

export function formatDate(date, rangeType) {
    if (rangeType === 'days') {
        return new Date(date).toLocaleString(locale, { month: 'short', day: 'numeric' });
    } else if (rangeType === 'months') {
        return new Date(date).toLocaleString(locale, { month: 'short', year: 'numeric' });
    }

    return date;
}
