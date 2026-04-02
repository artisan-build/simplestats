import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useLocale() {
    const page = usePage();

    const locale = computed(() => page.props.locale || 'en');
    const defaultLocale = computed(() => page.props.defaultLocale || 'en');
    const supportedLocales = computed(() => page.props.supportedLocales || ['en']);
    const localeUrls = computed(() => page.props.localeUrls || {});

    /**
     * Get the URL for the current page in a different locale.
     */
    function localeUrl(targetLocale) {
        return localeUrls.value[targetLocale] || '/';
    }

    /**
     * Prefix a path with the current locale (if not default).
     */
    function localizedPath(path) {
        if (locale.value === defaultLocale.value) {
            return path;
        }
        return `/${locale.value}${path === '/' ? '' : path}`;
    }

    return {
        locale,
        defaultLocale,
        supportedLocales,
        localeUrls,
        localeUrl,
        localizedPath,
    };
}
