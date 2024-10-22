import {XAAPU, XAPU, XAU} from "@/Helpers/StringHelpers.js";

const periodMap = {
    hours: 'hour',
    days: 'day',
    weeks: 'week',
    months: 'month',
    years: 'year',
};

export function getKpiDefinitions(rangeType) {
    let periodName = periodMap[rangeType];
    return {
        pd_NET: {
            small: 'Rev',
            short: 'Revenue',
            description: 'Net Revenue at this '+periodName+'.',
        },
        pd_GROSS: {
            short: 'Gross',
            description: 'Gross Revenue at this '+periodName+'.',
        },
        pd_FEE: {
            short: 'Fee',
            description: 'Fee at this '+periodName+'.',
        },
        pd_VISITOR: {
            small: 'Visitors',
            short: 'Unique Visitors',
            description: 'Number of unique visitors at this '+periodName+'.',
        },
        pd_REG: {
            small: 'Regs',
            short: 'Registrations',
            description: 'Number of new registrations at this '+periodName+'.',
        },
        pd_CR: {
            small: 'CR',
            short: 'Conversion Rate',
            description: 'Percentage of unique visitors who registered at this '+periodName+' (Conversion Rate).',
        },
        pd_NAU: {
            short: 'NAU',
            long: 'New Active Users',
            description: 'Number of unique users who returned at this '+periodName+' to the app (=2nd login) after the registration (=1st login).',
        },
        pd_NAPU: {
            short: 'NAPU',
            long: 'New Active Paying Users',
            description: 'Number of unique users who did their first payment at this '+periodName+'.',
        },
        pd_DAU: {
            short: XAU(rangeType),
            long: XAU(rangeType, true),
            description: 'Number of unique users who logged in at this '+periodName+' (without 1st login = registration).',
        },
        pd_DAPU: {
            short: XAPU(rangeType),
            long: XAPU(rangeType, true),
            description: 'Number of unique users who logged in or registered at this '+periodName+' and did at least one payment over lifetime (including this '+periodName+')',
        },
        pd_DAAPU: {
            short: XAAPU(rangeType),
            long: XAAPU(rangeType, true),
            description: 'Number of unique users who logged in or registered during the given '+periodName+' and did at least one payment in the last 20 days (including this '+periodName+')',
        },
        pd_ARPDAU: {
            short: 'ARP' + XAU(rangeType),
            long: 'Average Revenue Per ' + XAU(rangeType),
            description: 'The average revenue per ' + XAU(rangeType, true),
        },
        lt_ARPU: {
            short: 'ARPU',
            long: 'Average Revenue Per User',
            description: 'The average revenue per User over lifetime.',
        },
        lt_ARPPU: {
            short: 'ARPPU',
            long: 'Average Revenue Per Paying User',
            description: 'The average revenue per paying User.',
        },
        lt_ARPV: {
            short: 'ARPV',
            long: 'Average Revenue Per Visitor',
            description: 'The average revenue per Unique Visitor over lifetime.',
        },
        lt_PU: {
            small: 'PU',
            short: 'Paying Users',
            description: 'Number of users who did at least one payment over lifetime (including this '+periodName+').',
        },
        lt_TAU: {
            small: 'Tot. Actives',
            short: 'Total Actives',
            description: 'Number of users who logged in at least twice over lifetime.',
        },
        lt_REG2DAU: {
            short: 'Reg. to ' + XAU(rangeType),
            description: 'Percentage of registered users who become '+XAU(rangeType, true)+'.',
        },
        lt_REG2PU: {
            short: 'Reg. to Pay',
            description: 'Percentage of registered users who become paying users.',
        },
        lt_REG2TAU: {
            short: 'Reg. to Active',
            description: 'Percentage of registered users who become active.',
        },
    }
}
