export const periodMap = {
    days: 'Daily',
    weeks: 'Weekly',
    months: 'Monthly',
};

export const periodSingular = {
    days: 'day',
    weeks: 'week',
    months: 'month',
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

