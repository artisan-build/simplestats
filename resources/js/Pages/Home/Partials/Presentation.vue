<script setup>
import {onMounted, ref} from 'vue';

const screens = [
    { key: 'dashboard', label: 'Dashboard', alt: 'Web analytics dashboard for Laravel apps', top: '14.8%' },
    { key: 'revenue', label: 'Revenue', alt: 'MRR and revenue analytics for Laravel apps', top: '21.2%' },
    { key: 'retention', label: 'Retention', alt: 'Cohort retention analytics for Laravel apps', top: '27.6%' },
    { key: 'users', label: 'Users', alt: 'Power user analytics for Laravel apps', top: '34.0%' },
    { key: 'campaigns', label: 'Campaigns', alt: 'Campaign ROI analytics for Laravel apps', top: '40.3%' },
];

const themes = [
    { name: 'light', class: 'block dark:hidden' },
    { name: 'dark', class: 'hidden dark:block' },
];

const activeScreen = ref('dashboard');

function screenshotPath(key, theme, ext) {
    return `images/screenshots/screenshot_${key}_${theme}.${ext}`;
}

onMounted(() => {
    // Warm the cache for the other screens once the browser is idle, so the
    // first hotspot click doesn't show a blank frame while the image loads.
    const preloadScreens = () => {
        const theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';

        screens
            .filter((screen) => screen.key !== activeScreen.value)
            .forEach((screen) => {
                new Image().src = screenshotPath(screen.key, theme, 'webp');
            });
    };

    if ('requestIdleCallback' in window) {
        requestIdleCallback(preloadScreens);
    } else {
        setTimeout(preloadScreens, 2000);
    }
});
</script>

<template>
    <section class="animationElement mt-20 flex justify-center px-4">
        <div class="w-full max-w-screen-xl">
            <div class="relative">
                <template v-for="screen in screens" :key="screen.key">
                    <picture
                        v-for="theme in themes"
                        v-show="screen.key === activeScreen"
                        :key="`${screen.key}-${theme.name}`"
                        :class="theme.class"
                    >
                        <source :srcset="screenshotPath(screen.key, theme.name, 'webp')" type="image/webp">
                        <img width="1280" height="832" :src="screenshotPath(screen.key, theme.name, 'png')" :alt="screen.alt" loading="lazy" fetchpriority="low">
                    </picture>
                </template>
                <button
                    v-for="screen in screens"
                    v-show="screen.key !== activeScreen"
                    :key="`hotspot-${screen.key}`"
                    class="absolute -translate-x-1/2 -translate-y-1/2"
                    :style="{ top: screen.top, left: '6.65%' }"
                    :aria-label="`Show ${screen.label} screenshot`"
                    @click="activeScreen = screen.key"
                >
                    <span class="relative flex size-3">
                        <span class="absolute inline-flex size-full animate-ping rounded-full bg-indigo-300 opacity-75"></span>
                        <span class="relative inline-flex size-3 rounded-full bg-indigo-400"></span>
                    </span>
                </button>
            </div>
        </div>
    </section>
</template>

<style scoped>
.animationElement {
  animation: fadeInUp 0.8s ease forwards;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(2rem);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
