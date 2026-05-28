<script setup>
import {computed, ref} from 'vue';

const props = defineProps({
    domain: {
        type: String,
        default: null,
    },
    size: {
        type: String,
        default: 'size-5',
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
        :class="[size, 'inline-block shrink-0']"
        :src="faviconSrc"
        :alt="domain"
        @error="hasError = true"
    >
    <img
        v-else
        :class="[size, 'inline-block shrink-0 dark:invert dark:brightness-90']"
        src="/icons/unknown.svg"
        alt="Unknown"
    >
</template>
