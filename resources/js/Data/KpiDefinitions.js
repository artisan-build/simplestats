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
        mrr: {
            short: 'MRR',
            long: 'Monthly Recurring Revenue',
            description: 'Active recurring revenue as of the end of the selected period, normalized to a monthly amount (yearly plans counted as net / 12).',
        },
        arr: {
            short: 'ARR',
            long: 'Annual Recurring Revenue',
            description: 'Annualized recurring revenue (MRR × 12).',
        },
        pd_net: {
            small: 'Rev',
            short: 'Revenue',
            description: 'Net Revenue at this '+periodName+'.',
        },
        pd_gross: {
            short: 'Gross',
            description: 'Gross Revenue at this '+periodName+'.',
        },
        pd_fee: {
            short: 'Fee',
            description: 'Fee at this '+periodName+'.',
        },
        pd_visitor: {
            small: 'Visitors',
            short: 'Unique Visitors',
            description: 'Number of unique visitors at this '+periodName+'.',
        },
        pd_reg: {
            small: 'Regs',
            short: 'Registrations',
            description: 'Number of new registrations at this '+periodName+'.',
        },
        pd_cr: {
            small: 'CR',
            short: 'Conversion Rate',
            description: 'Percentage of unique visitors who registered at this '+periodName+' (Conversion Rate).',
        },
        pd_nau: {
            short: 'NAU',
            long: 'New Active Users',
            description: 'Number of unique users who returned at this '+periodName+' to the app (=2nd login) after the registration (=1st login).',
        },
        pd_napu: {
            short: 'NAPU',
            long: 'New Active Paying Users',
            description: 'Number of unique users who did their first payment at this '+periodName+'.',
        },
        pd_dau: {
            short: XAU(rangeType),
            long: XAU(rangeType, true),
            description: 'Number of unique users who logged in at this '+periodName+' (without 1st login = registration).',
        },
        pd_dapu: {
            short: XAPU(rangeType),
            long: XAPU(rangeType, true),
            description: 'Number of unique users who logged in or registered at this '+periodName+' and did at least one payment over lifetime (including this '+periodName+')',
        },
        pd_daapu: {
            short: XAAPU(rangeType),
            long: XAAPU(rangeType, true),
            description: 'Number of unique users who logged in or registered during the given '+periodName+' and did at least one payment in the last 20 days (including this '+periodName+')',
        },
        pd_arpdau: {
            short: 'ARP' + XAU(rangeType),
            long: 'Average Revenue Per ' + XAU(rangeType),
            description: 'The average revenue per ' + XAU(rangeType, true),
        },
        lt_arpu: {
            short: 'ARPU',
            long: 'Average Revenue Per User',
            description: 'The average revenue per User over lifetime.',
        },
        lt_arppu: {
            short: 'ARPPU',
            long: 'Average Revenue Per Paying User',
            description: 'The average revenue per paying User over lifetime.',
        },
        lt_arpv: {
            short: 'ARPV',
            long: 'Average Revenue Per Visitor',
            description: 'The average revenue per Unique Visitor over lifetime.',
        },
        lt_pu: {
            small: 'PU',
            short: 'Paying Users',
            description: 'Number of users who did at least one payment over lifetime (including this '+periodName+').',
        },
        lt_tau: {
            small: 'Tot. Actives',
            short: 'Total Actives',
            description: 'Number of users who logged in at least twice over lifetime.',
        },
        lt_reg2dau: {
            short: 'Reg. to ' + XAU(rangeType),
            description: 'Percentage of registered users who become '+XAU(rangeType, true)+'.',
        },
        lt_reg2pu: {
            short: 'Reg. to Pay',
            description: 'Percentage of registered users who become paying users.',
        },
        lt_reg2tau: {
            short: 'Reg. to Active',
            description: 'Percentage of registered users who become active.',
        },
    }
}
