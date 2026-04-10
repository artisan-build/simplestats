import {trans} from 'laravel-vue-i18n';

export const competitors = [
    {key: 'simplestats', name: 'SimpleStats'},
    {key: 'plausible', name: 'Plausible'},
    {key: 'fathom', name: 'Fathom'},
    {key: 'ga', name: 'Google Analytics'},
];

// true = fully supported, 'partial' = limited/manual support, false = not supported
export const comparisonFeatures = [
    {
        label: () => trans('Revenue Attribution'),
        simplestats: true,
        plausible: 'partial',
        fathom: false,
        ga: true,
    },
    {
        label: () => trans('User & Payment tracking'),
        simplestats: true,
        plausible: false,
        fathom: false,
        ga: 'partial',
    },
    {
        label: () => trans('Server-side tracking'),
        simplestats: true,
        plausible: false,
        fathom: false,
        ga: false,
    },
    {
        label: () => trans('Ad-blocker proof'),
        simplestats: true,
        plausible: false,
        fathom: false,
        ga: false,
    },
    {
        label: () => trans('No JavaScript required'),
        simplestats: true,
        plausible: false,
        fathom: false,
        ga: false,
    },
    {
        label: () => trans('Laravel-native'),
        simplestats: true,
        plausible: false,
        fathom: false,
        ga: false,
    },
    {
        label: () => trans('No cookie banner required'),
        simplestats: true,
        plausible: true,
        fathom: true,
        ga: false,
    },
    {
        label: () => trans('Self-hosting option'),
        simplestats: true,
        plausible: true,
        fathom: false,
        ga: false,
    },
];
