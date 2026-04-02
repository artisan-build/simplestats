<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Welcome from "@/Pages/Home/Partials/Welcome.vue";
import Footer from "@/Components/Footer.vue";

defineProps({
    plans: Array,
    banners: Array,
    discount: Number,
});
</script>

<template>
    <Head :title="$t('The missing analytics tool for Laravel applications')" />

    <Welcome :plans="plans" :banners="banners" :discount="discount" />

    <Footer />
</template>
