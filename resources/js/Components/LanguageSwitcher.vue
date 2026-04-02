<script setup>
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue';
import { usePage } from '@inertiajs/vue3';
import { useLocale } from '@/Composables/useLocale.js';
import { get as getFlag } from 'country-flag-emoji';

const { locale, supportedLocales, localeUrl } = useLocale();
const localeConfig = usePage().props.localeConfig;

const getEmoji = (loc) => getFlag(localeConfig[loc]?.country)?.emoji ?? '';
</script>

<template>
    <Menu as="div" class="relative inline-block text-left">
        <MenuButton class="inline-flex items-center gap-1.5 hover:text-indigo-600 text-sm font-medium">
            <span class="text-base">{{ getEmoji(locale) }}</span>
            {{ localeConfig[locale]?.label ?? locale.toUpperCase() }}
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </MenuButton>

        <transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <MenuItems class="absolute left-0 bottom-full mb-2 w-40 origin-bottom-left rounded-md bg-white dark:bg-dark-800 shadow-lg ring-1 ring-black/5 dark:ring-dark-600 focus:outline-none z-50">
                <div class="py-1">
                    <MenuItem v-for="loc in supportedLocales" :key="loc" v-slot="{ active }">
                        <a
                            :href="localeUrl(loc)"
                            class="flex items-center gap-2 px-4 py-2 text-sm"
                            :class="[
                                active ? 'bg-gray-100 dark:bg-dark-700' : '',
                                loc === locale ? 'font-semibold text-indigo-600' : 'text-gray-700 dark:text-dark-300'
                            ]"
                        >
                            <span class="text-base">{{ getEmoji(loc) }}</span>
                            {{ localeConfig[loc]?.label ?? loc.toUpperCase() }}
                        </a>
                    </MenuItem>
                </div>
            </MenuItems>
        </transition>
    </Menu>
</template>
