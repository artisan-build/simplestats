const periodMap = {
    days: 'Daily',
    weeks: 'Weekly',
    months: 'Monthly',
};

export function XAU(type, long) {
    let period = periodMap[type];
    let short = period.charAt(0).toUpperCase() + 'AU';
    if (long === true) {
        return period+' Active Users ('+short+')';
    } else {
        return short;
    }
}

export function XAPU(type, long) {
    let period = periodMap[type];
    let short = period.charAt(0).toUpperCase() + 'APU';
    if (long === true) {
        return XAU(type)+' Paying ('+short+')';
    } else {
        return short;
    }
}

export function XAAPU(type, long) {
    let period = periodMap[type];
    let short = period.charAt(0).toUpperCase() + 'AAPU';
    if (long === true) {
        return XAU(type)+' Active Paying ('+short+')';
    } else {
        return short;
    }
}

export function ARPXAU(type) {
    return 'ARP' + type.charAt(0).toUpperCase() + 'AU';
}

