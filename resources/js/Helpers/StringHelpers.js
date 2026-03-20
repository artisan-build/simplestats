export const periodMap = {
    hours: 'Hourly',
    days: 'Daily',
    weeks: 'Weekly',
    months: 'Monthly',
    years: 'Yearly',
};

export const periodSingular = {
    hours: 'hour',
    days: 'day',
    weeks: 'week',
    months: 'month',
    years: 'year',
};

export function XAU(type, long) {
    let period = periodMap[type];
    if (long === true) {
        return period+' Active Users';
    } else {
        return period.charAt(0).toUpperCase() + 'AU';
    }
}

export function XAPU(type, long) {
    let period = periodMap[type];
    if (long === true) {
        return period+' Active Paying User';
    } else {
        return period.charAt(0).toUpperCase() + 'APU';
    }
}

export function XAAPU(type, long) {
    let period = periodMap[type];
    if (long === true) {
        return period+' Active - Active Paying User';
    } else {
        return period.charAt(0).toUpperCase() + 'AAPU';
    }
}

export function ARPXAU(type) {
    return 'ARP' + type.charAt(0).toUpperCase() + 'AU';
}

export function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

export function truncate(str, { length = 30 } = {}) {
    return str.length > length ? str.slice(0, length - 3) + '...' : str;
}

export function toSnakeCase(str) {
    return str
        .replace(/([a-z\d])([A-Z])/g, '$1_$2')
        .replace(/([A-Z]+)([A-Z][a-z])/g, '$1_$2')
        .replace(/[^\w]+/g, '_')
        .replace(/^_+|_+$/g, '')
        .toLowerCase();
}

export function sample(arr) {
    return arr[Math.floor(Math.random() * arr.length)];
}

