<script setup>
import {computed, ref} from 'vue';

const props = defineProps({
    domain: {
        type: String,
        default: null,
    },
});

const hasError = ref(false);

const faviconSrc = computed(() => {
    return `https://t3.gstatic.com/faviconV2?client=SOCIAL&type=FAVICON&fallback_opts=TYPE,SIZE,URL&url=http://${props.domain ?? ''}&size=32`;
});
</script>

<template>
    <img
        v-if="!hasError"
        class="w-4 h-4 inline-block shrink-0"
        :src="faviconSrc"
        :alt="domain"
        @error="hasError = true"
    >
    <img
        v-else
        class="w-4 h-4 inline-block shrink-0 dark:invert dark:brightness-90"
        src="/icons/unknown.svg"
        alt="Unknown"
    >
</template>
