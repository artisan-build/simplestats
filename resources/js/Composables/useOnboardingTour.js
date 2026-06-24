import {driver} from 'driver.js';
import 'driver.js/dist/driver.css';
import {usePage} from '@inertiajs/vue3';
import axios from 'axios';

export function useOnboardingTour() {
    const page = usePage();

    function isDesktop() {
        return window.innerWidth >= 1024;
    }

    function shouldShowTour() {
        if (!isDesktop()) return false;
        if (page.props.tourCompleted) return false;
        if (!page.props.onboardingPassed) return false;
        return true;
    }

    function completeTour() {
        axios.post(route('user-settings.complete-tour'));
    }

    function completeCampaignsTour() {
        axios.post(route('user-settings.complete-campaigns-tour'));
    }

    function waitForElement(selector, maxWait = 3000) {
        return new Promise((resolve) => {
            const start = Date.now();
            function check() {
                if (document.querySelector(selector)) return resolve(true);
                if (Date.now() - start > maxWait) return resolve(false);
                requestAnimationFrame(check);
            }
            check();
        });
    }

    function isVisible(selector) {
        const el = document.querySelector(selector);
        return el && el.offsetParent !== null;
    }

    function runTour(steps, onComplete) {
        const tourDriver = driver({
            showProgress: true,
            allowClose: true,
            overlayColor: 'black',
            popoverClass: 'simplestats-tour',
            disableActiveInteraction: true,
            overlayClickBehavior: () => {},
            onDestroyStarted: () => {
                onComplete();
                tourDriver.destroy();
            },
            steps,
        });

        tourDriver.drive();
    }

    async function startTour() {
        if (!shouldShowTour()) return;

        await waitForElement('[data-tour="stats-groups"]');

        const steps = [
            {
                popover: {
                    title: 'Welcome to SimpleStats!',
                    description: 'Want a quick tour of the dashboard? It only takes a few seconds. You can close this at any time to skip.',
                },
            },
            {
                element: '[data-tour="presets"]',
                popover: {
                    title: 'Date Presets',
                    description: 'Quickly switch between time ranges. Click the gear icon to customize which presets are visible or create your own.',
                    side: 'bottom',
                    align: 'end',
                },
            },
            {
                element: '[data-tour="advanced-toggle"]',
                popover: {
                    title: 'Detail level',
                    description: 'Switch between Simple, Advanced, and Business to show more or fewer KPIs, from a focused overview to the full breakdown.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="chart-info"]',
                popover: {
                    title: 'Chart Info',
                    description: 'Hover over this icon to see a description of each value and what it means.',
                    side: 'left',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="stats-groups"]',
                popover: {
                    title: 'Tracking Groups',
                    description: 'Your analytics data grouped by source, page, location, and device. Use the tabs to switch views, and click any row to filter the dashboard.',
                    side: 'top',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="profile-dropdown"]',
                popover: {
                    title: 'Your Profile',
                    description: 'Access notification settings, data import and your current subscription details.',
                    side: 'right',
                    align: 'end',
                },
            },
        ].filter(step => {
            if (!step.element) return true;
            return isVisible(step.element);
        });

        if (steps.length <= 1) return;

        runTour(steps, completeTour);
    }

    async function startCampaignsTour() {
        if (!isDesktop()) return;
        if (page.props.campaignsTourCompleted) return;

        await waitForElement('[data-tour="campaign-metrics"]');

        const steps = [
            {
                element: '[data-tour="campaigns-intro"]',
                popover: {
                    title: 'Analyse your ad campaigns',
                    description: 'No tracking pixel needed. Enter your ad spend and SimpleStats matches it against the real registrations and revenue from your own database, server-side, with no ad-blocker or consent loss.',
                    side: 'bottom',
                    align: 'start',
                },
            },
            {
                element: '[data-tour="add-campaign"]',
                popover: {
                    title: 'Track a campaign',
                    description: 'Create a campaign, link its utm_campaign value(s) and enter what you spent on Meta, Google & co.',
                    side: 'bottom',
                    align: 'end',
                },
            },
            {
                element: '[data-tour="campaign-metrics"]',
                popover: {
                    title: "Metrics your ad portal can't show",
                    description: 'See real ROAS, CAC, net profit and lifetime value broken down per campaign, not just clicks and pixel-estimated conversions.',
                    side: 'top',
                    align: 'start',
                },
            },
        ].filter(step => isVisible(step.element));

        if (!steps.length) return;

        runTour(steps, completeCampaignsTour);
    }

    return {startTour, startCampaignsTour};
}
