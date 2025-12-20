<script setup>
import {snakeCase} from "lodash";
import {get as getFlag} from 'country-flag-emoji';
import {ref} from "vue";

const props = defineProps({
    statsName: String,
    statsType: String,
    statsIconType: String,
})

const hasError = ref(false);
// unknown fallback
const isUnknown = props.statsName === null || props.statsName === 'Direct / Unknown';
</script>

<template>
    <img v-if="isUnknown || hasError" class="w-4 h-4 align-sub inline-block dark:invert dark:brightness-90" src="/icons/unknown.svg" alt="Unknown">
    <img v-else-if="statsIconType === 'favicon'" class="w-4 h-4 align-sub inline-block" :src="'https://icons.duckduckgo.com/ip3/'+statsName+'.ico'" :alt="statsName">
    <img v-else-if="statsIconType === 'path' && snakeCase(statsType) === 'device_platform'" class="w-4 h-4 align-sub inline-block" :src="'/icons/'+ snakeCase(statsType) +'/'+ snakeCase(statsName) +'.png'" :alt="statsName" @error="hasError = true">
    <img v-else-if="statsIconType === 'path' && snakeCase(statsType) === 'device_type'" class="w-4 h-4 align-sub inline-block dark:invert dark:brightness-90" :src="'/icons/'+ snakeCase(statsType) +'/'+ snakeCase(statsName) +'.svg'" :alt="statsName" @error="hasError = true">
    <img v-else-if="statsIconType === 'path'" class="w-4 h-4 align-sub inline-block" :src="'/icons/'+ snakeCase(statsType) +'/'+ snakeCase(statsName) +'.svg'" :alt="statsName" @error="hasError = true">
    <span v-else-if="statsIconType === 'country'" class="text-lg align-sub mt-1 inline-block">{{ getFlag((statsName.match(/\((.*?)\)/) || [])[1])?.emoji ?? '🌎' }}</span>
</template>
