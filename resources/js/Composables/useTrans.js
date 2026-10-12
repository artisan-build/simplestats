import { inject } from 'vue';
import { trans as sharedTrans } from 'laravel-vue-i18n';

// The SSR entry installs i18nVue with `shared: false` (one instance per request), so the
// global `trans()` export would hit an empty shared instance there and render English.
// Resolving the app's own instance keeps SSR and client output identical.
export function useTrans() {
    const i18n = inject('i18n', null);

    return i18n ? (key, replacements) => i18n.trans(key, replacements) : sharedTrans;
}
