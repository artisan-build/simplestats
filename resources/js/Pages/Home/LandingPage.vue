<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Welcome from "@/Pages/Home/Partials/Welcome.vue";
import Footer from "@/Components/Footer.vue";

defineProps({
    plans: Array,
});
</script>

<template>
    <Head title="Welcome" />

    <Welcome :plans="plans" />

    <Footer />
</template>
