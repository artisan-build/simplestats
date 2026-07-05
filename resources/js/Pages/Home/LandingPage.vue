<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Welcome from "@/Pages/Home/Partials/Welcome.vue";
import Footer from "@/Components/Footer.vue";

defineProps({
    plans: Array,
    banners: Array,
    discount: Number,
    latestPosts: Array,
});
</script>

<template>
    <Head :title="$t('Revenue attribution analytics tool for Laravel apps')" />

    <Welcome :plans="plans" :banners="banners" :discount="discount" :latest-posts="latestPosts" />

    <Footer />
</template>
