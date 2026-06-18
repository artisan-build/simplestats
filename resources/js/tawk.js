import { router } from '@inertiajs/vue3';

const TAWK_SRC = 'https://embed.tawk.to/6601f04ea0c6737bd124880f/1hprp5d8g';
const LANDING_COMPONENT = 'Home/LandingPage';
const IDLE_LOAD_DELAY = 5000;
const INTERACTION_EVENTS = ['scroll', 'click', 'touchstart', 'keydown'];

let scriptInjected = false;
let shouldBeVisible = false;

function injectScript() {
    if (scriptInjected) {
        return;
    }
    scriptInjected = true;

    window.Tawk_API = window.Tawk_API || {};
    window.Tawk_LoadStart = new Date();

    // Re-sync once loaded: the user may have navigated away before init finished.
    window.Tawk_API.onLoad = function () {
        applyVisibility();
    };

    const script = document.createElement('script');
    script.async = true;
    script.src = TAWK_SRC;
    script.charset = 'UTF-8';
    script.setAttribute('crossorigin', '*');

    const firstScript = document.getElementsByTagName('script')[0];
    firstScript.parentNode.insertBefore(script, firstScript);
}

function applyVisibility() {
    const api = window.Tawk_API;
    if (!api || typeof api.showWidget !== 'function' || typeof api.hideWidget !== 'function') {
        return;
    }

    if (shouldBeVisible) {
        api.showWidget();
    } else {
        api.hideWidget();
    }
}

function scheduleLazyLoad() {
    setTimeout(injectScript, IDLE_LOAD_DELAY);
    INTERACTION_EVENTS.forEach((event) => {
        document.addEventListener(event, injectScript, { once: true, passive: true });
    });
}

function syncWidget(component) {
    shouldBeVisible = component === LANDING_COMPONENT;

    if (shouldBeVisible && !scriptInjected) {
        scheduleLazyLoad();
    }

    applyVisibility();
}

export function initTawk(initialComponent) {
    const isProduction = import.meta.env.VITE_APP_ENV === 'production';
    const isSaas = import.meta.env.VITE_APP_TYPE === 'saas';

    if (!isProduction || !isSaas) {
        return;
    }

    syncWidget(initialComponent);

    router.on('navigate', (event) => {
        syncWidget(event.detail.page.component);
    });
}
