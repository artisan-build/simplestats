<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Welcome from "@/Pages/Home/Partials/Welcome.vue";
import ComingSoon from "@/Pages/Home/Partials/ComingSoon.vue";
import Footer from "@/Components/Footer.vue";

defineProps({
    comingSoon: Boolean,
    plans: Array,
});
</script>

<template>
    <Head title="Welcome" />

    <div v-if="comingSoon">
        <ComingSoon :plans="plans" />
    </div>
    <div v-else>
        <Welcome :plans="plans" />
    </div>

    <Footer />
</template>
