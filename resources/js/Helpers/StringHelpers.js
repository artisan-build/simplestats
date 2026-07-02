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

// Mirrors StatsRangeType::retentionCheckpointDays(): checkpoints sit at 1 range
// unit, ~1/4 and ~1/2 of the range, so the last column is never structurally
// empty (the cohort only spans the range itself, a checkpoint at full range
// length has an always-censored denominator). Hours fall back to a day ladder.
const retentionUnits = {days: 'd', weeks: 'w', months: 'm', years: 'y'};
const retentionUnitWords = {d: 'day', w: 'week', m: 'month', y: 'year'};

export function retentionCheckpointLabels(type, rangeAmount) {
    let unit = retentionUnits[type];
    let checkpoints;

    if (! unit || ! Number(rangeAmount)) {
        unit = 'd';
        checkpoints = [1, 7, 30];
    } else {
        const second = Math.max(2, Math.floor(rangeAmount / 4));
        checkpoints = [1, second, Math.max(second + 1, Math.floor(rangeAmount / 2))];
    }

    return checkpoints.map((amount) => {
        const span = `${amount} ${retentionUnitWords[unit]}${amount === 1 ? '' : 's'}`;

        return {
            label: `After ${amount}${unit}`,
            labelSmall: `${unit.toUpperCase()}${amount}`,
            tooltip: {
                title: `Active after ${span}`,
                text: `Came back ${span} or later after signing up (any later return counts, `
                    + `while the cohort grid only counts a return inside that exact ${retentionUnitWords[unit]}). `
                    + `Signups younger than ${span} are excluded; '-' means none are old enough yet.`,
            },
        };
    });
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

