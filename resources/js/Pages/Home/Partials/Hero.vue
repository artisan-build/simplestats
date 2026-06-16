<script setup>
import {router} from "@inertiajs/vue3";
import {useLocale} from "@/Composables/useLocale.js";
import {trans} from 'laravel-vue-i18n';
import {computed} from 'vue';
import {testimonials} from "@/Data/testimonials.js";
import {HandRaisedIcon} from "@heroicons/vue/24/outline/index.js";

const { localizedPath } = useLocale();
const appEnv = import.meta.env.VITE_APP_ENV;
const appName = import.meta.env.VITE_APP_NAME;

const stats = [
    { term: 'Revenue', description: 'Attribution' },
    { term: '100%', description: 'accurate' },
    { term: 'GDPR', description: 'compliant' },
    { term: '5 min', description: 'setup' },
];

const heroHeadline = computed(() =>
    trans('Analytics for :laravel', {
        laravel: '<span class="relative inline-block"><span class="absolute inset-x-0 bottom-2 h-3 -skew-x-6 bg-indigo-200/70 dark:bg-indigo-500/30"></span><span class="relative">Laravel</span></span>',
    })
);

const heroSubhead = computed(() =>
    trans('Track visitors, registrations, and payments. Discover which channels actually drive revenue, not just traffic. Server-side, GDPR compliant, 100% accurate. Set up in just a few :minutes.', {
        minutes: `<a href="/docs" target="_blank" class="font-bold text-indigo-500">${trans('minutes')}</a>`,
    })
);

function trackGetStartedClick() {
    window.axios.post(route('tracking.get-started-click')).finally(() => {
        router.visit(localizedPath('/register'));
    });
}
</script>

<template>
    <section class="animationElement text-center pt-24">
        <h1
            class="m-auto mb-10 text-5xl font-extrabold tracking-tight leading-none md:text-6xl xl:text-7xl dark:text-white px-6 sm:px-3 max-w-screen-lg"
            v-html="heroHeadline"
        ></h1>
        <div class="relative m-auto mb-8 font-light text-gray-500 lg:mb-10 md:text-lg lg:text-xl dark:text-dark-400 px-6 sm:px-3 max-w-screen-md">
            <span v-html="heroSubhead"></span>
            <a href="/docs/introduction.html#server-side-vs-client-side-tracking" target="_blank" class="hidden xl:flex absolute right-0 top-0 -mr-64 -mt-10 rotate-[18deg] hover:rotate-[12deg] transition-transform">
                <div class="rounded-2xl bg-white dark:bg-dark-800 ring-1 ring-gray-950/10 dark:ring-white/10 shadow-xl shadow-indigo-600/10 px-4 py-3 flex items-center gap-3">
                    <div class="flex items-center justify-center size-9 rounded-xl bg-indigo-600 text-white">
                        <HandRaisedIcon class="size-5" />
                    </div>
                    <div class="text-left">
                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $t("Can't be blocked") }}</div>
                        <div class="text-xs text-gray-500 dark:text-dark-400">{{ $t('by ad blockers') }}</div>
                    </div>
                </div>
            </a>
        </div>

        <dl class="mb-10 mx-auto max-w-3xl px-6 hidden sm:grid sm:grid-cols-4 gap-y-3 tabular-nums">
            <div
                v-for="(stat, index) in stats"
                :key="stat.term"
                class="flex flex-col items-center sm:px-6 border-gray-950/10 dark:border-white/10"
                :class="{ 'sm:border-l': index > 0 }"
            >
                <dt class="text-2xl md:text-3xl font-extrabold text-indigo-600 dark:text-indigo-400">{{ $t(stat.term) }}</dt>
                <dd class="mt-1 text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-dark-400">{{ $t(stat.description) }}</dd>
            </div>
        </dl>

        <div class="relative flex flex-col md:flex-row justify-center">
            <div class="pointer-events-none absolute inset-0 mx-auto w-72 h-16 bg-indigo-500/30 blur-3xl"></div>
            <div class="relative">
                <button v-if="$page.props.canRegister" class="mb-4 inline-flex items-center justify-center px-6 py-3 mx-1.5 text-base font-medium text-center text-white rounded-lg bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-600/25 focus:ring-4 focus:ring-indigo-300 dark:focus:ring-indigo-900 transition hover:scale-[1.02]" @click="trackGetStartedClick">
                    {{ $t('Get started for free') }}
                    <svg class="w-5 h-5 ml-2 -mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                </button>
            </div>
            <div class="relative">
                <a v-if="$page.props.canLogin" href="https://demo.simplestats.io/login" :target="(appEnv === 'demo') ? '_self' : '_blank'" class="mb-4 mx-1.5 inline-flex items-center justify-center px-6 py-3 text-base font-medium text-center text-gray-900 dark:text-white border border-gray-300 rounded-lg bg-white dark:bg-dark-800 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:border-dark-700 dark:hover:bg-dark-700 dark:focus:ring-gray-800">
                    {{ $t('Live demo') }}
                </a>
                <a href="/blog/server-side-analytics-laravel" class="mb-4 mx-1.5 inline-flex items-center justify-center px-6 py-3 text-base font-medium text-center text-gray-900 dark:text-white border border-gray-300 rounded-lg bg-white dark:bg-dark-800 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:border-dark-700 dark:hover:bg-dark-700 dark:focus:ring-gray-800">
                    {{ $t('Why :appName?', { appName }) }}
                </a>
            </div>
        </div>

        <div class="mt-10 flex flex-col items-center">
            <div class="flex -space-x-2">
                <a
                    v-for="t in testimonials"
                    :key="t.name"
                    v-tooltip.bottom="{
                        content: `<div class='flex flex-col space-y-2 max-w-72 p-2 text-base text-left text-white'><p class='font-bold text-indigo-300'>${t.name}</p><p class='text-sm text-gray-300'>${t.title}${t.company ? ', ' + t.company : ''}</p><p class='text-sm text-gray-100'>${t.descr}</p></div>`,
                        html: true,
                    }"
                    :href="t.social_link || t.company_link"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="block size-10 shrink-0"
                >
                    <img
                        :src="t.avatar"
                        :alt="t.name"
                        class="block size-10 rounded-full object-cover ring-2 ring-white dark:ring-dark-900 transition-all hover:scale-110"
                    />
                </a>
            </div>
            <p class="mt-3 text-xs text-gray-500 dark:text-dark-400">{{ $t('Loved by the Laravel community') }}</p>
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
