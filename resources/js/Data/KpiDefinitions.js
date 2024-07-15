import {XAAPU, XAPU, XAU} from "@/Helpers/StringHelpers.js";

const periodMap = {
    days: 'day',
    weeks: 'week',
    months: 'month',
};

export function getKpiDefinitions(rangeType) {
    let periodName = periodMap[rangeType];
    return {
        per_date: {
            NET: {
                short: 'Net',
                description: 'Net Revenue at this '+periodName+'.',
            },
            GROSS: {
                short: 'Gross',
                description: 'Gross Revenue at this '+periodName+'.',
            },
            FEE: {
                short: 'Fee',
                description: 'Fee at this '+periodName+'.',
            },
            VISITOR: {
                short: 'Visitors',
                description: 'Number of unique visitors at this '+periodName+'.',
            },
            REG: {
                short: 'Regs',
                description: 'Number of new registrations at this '+periodName+'.',
            },
            CR: {
                short: 'CR',
                description: 'Percentage of unique visitors who registered at this '+periodName+' (Conversion Rate).',
            },
            NAU: {
                short: 'NAU',
                long: 'New Active Users',
                description: 'Number of unique users who returned at this '+periodName+' to the app (=2nd login) after the registration (=1st login).',
            },
            NAPU: {
                short: 'NAPU',
                long: 'New Active Paying Users',
                description: 'Number of unique users who did their first payment at this '+periodName+'.',
            },
            DAU: {
                short: XAU(rangeType),
                long: XAU(rangeType, true),
                description: 'Number of unique users who logged in at this '+periodName+' (without 1st login = registration).',
            },
            DAPU: {
                short: XAPU(rangeType),
                long: XAPU(rangeType, true),
                description: 'Number of unique users who logged in or registered at this '+periodName+' and did at least one payment over lifetime (including this '+periodName+')',
            },
            DAAPU: {
                short: XAAPU(rangeType),
                long: XAAPU(rangeType, true),
                description: 'Number of unique users who logged in or registered during the given '+periodName+' and did at least one payment in the last 20 days (including this '+periodName+')',
            },
            ARPDAU: {
                short: 'ARP' + XAU(rangeType),
                long: 'Average Revenue Per ' + XAU(rangeType),
                description: 'The average revenue per ' + XAU(rangeType, true),
            },
        },
        lifetime: {
            NET: {
                short: 'Total Net',
                description: 'Net Revenue over lifetime.',
            },
            GROSS: {
                short: 'Total Gross',
                description: 'Gross Revenue over lifetime.',
            },
            FEE: {
                short: 'Total Fee',
                description: 'Fee over lifetime.',
            },
            ARPU: {
                short: 'ARPU',
                long: 'Average Revenue Per User',
                description: 'The average revenue per User (how much is a user/lead/reg worth in money).',
            },
            ARPPU: {
                short: 'ARPPU',
                long: 'Average Revenue Per Paying User',
                description: 'The average revenue per paying User.',
            },
            VISITOR: {
                short: 'Total Visitors',
                description: 'Number of unique visitors over lifetime.',
            },
            REG: {
                short: 'Total Regs',
                description: 'Number of registrations over lifetime.',
            },
            CR: {
                short: 'Total CR',
                description: 'Percentage of unique visitors who registered (Conversion Rate).',
            },
            PU: {
                short: 'Paying Users',
                description: 'Number of paying users over lifetime.',
            },
            TAU: {
                short: 'Total Actives',
                description: 'Number of users who logged in at least twice over lifetime.',
            },
            REG2DAU: {
                short: 'Reg. to ' + XAU(rangeType),
                description: 'Percentage of registered users who become '+XAU(rangeType, true)+'.',
            },
            REG2PU: {
                short: 'Reg. to Pay',
                description: 'Percentage of registered users who become paying users.',
            },
            REG2TAU: {
                short: 'Reg. to Active',
                description: 'Percentage of registered users who become active.',
            },
        },
    }
};
