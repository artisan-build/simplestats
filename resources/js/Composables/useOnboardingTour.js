import {driver} from 'driver.js';
import 'driver.js/dist/driver.css';
import {usePage} from '@inertiajs/vue3';
import axios from 'axios';

export function useOnboardingTour() {
    const page = usePage();

    function shouldShowTour() {
        if (window.innerWidth < 1024) return false;
        if (page.props.tourCompleted) return false;
        if (!page.props.onboardingPassed) return false;
        return true;
    }

    function completeTour() {
        axios.post(route('user-settings.complete-tour'));
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

    async function startTour() {
        if (!shouldShowTour()) return;

        await waitForElement('[data-tour="stats-groups"] button');

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
                    title: 'Advanced View',
                    description: 'Toggle between a simple overview and a detailed breakdown with additional KPIs like ARPU, ARPV, and conversion rates.',
                    side: 'bottom',
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
                    description: 'Access Notifications, Data Import, and your team settings from here.',
                    side: 'right',
                    align: 'end',
                },
            },
        ].filter(step => {
            if (!step.element) return true;
            return isVisible(step.element);
        });

        if (steps.length <= 1) return;

        const tourDriver = driver({
            showProgress: true,
            allowClose: true,
            overlayColor: 'black',
            popoverClass: 'simplestats-tour',
            onDestroyStarted: () => {
                completeTour();
                tourDriver.destroy();
            },
            steps,
        });

        tourDriver.drive();
    }

    return {startTour};
}
