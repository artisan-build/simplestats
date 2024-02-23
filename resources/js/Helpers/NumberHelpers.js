const lang = navigator.language;

export function formatNumber(value, additionalOptions) {
    if (typeof value !== 'number' || isNaN(value)) {
        return '-';
    }

    let options = {};

    if (additionalOptions && typeof additionalOptions === 'object') {
        options = { ...options, ...additionalOptions };
    }

    return new Intl.NumberFormat(lang, options).format(value);
}

export function formatCurrency(value, currency, additionalOptions) {
    if (typeof value !== 'number' || isNaN(value)) {
        return '-';
    }

    let options = {
        style: "currency",
        currency: currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    };

    if (additionalOptions && typeof additionalOptions === 'object') {
        options = { ...options, ...additionalOptions };
    }

    return new Intl.NumberFormat(lang, options).format(value);
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

    return new Intl.NumberFormat(lang, options).format(value)+'%';
}
