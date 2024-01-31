<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Welcome from "@/Pages/Home/Partials/Welcome.vue";
import ComingSoon from "@/Pages/Home/Partials/ComingSoon.vue";

defineProps({
    comingSoon: Boolean,
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Welcome" />

    <div v-if="comingSoon">
        <ComingSoon />
    </div>
    <div v-else>
        <Welcome :canLogin="canLogin" :canRegister="canRegister" />
    </div>
</template>
