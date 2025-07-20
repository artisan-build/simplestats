<script setup>
import Gradient from "@/Pages/Home/Partials/Gradient.vue";
import Banners from "@/Pages/Home/Partials/Banners.vue";
import Header from "@/Pages/Home/Partials/Header.vue";
import Hero from "@/Pages/Home/Partials/Hero.vue";
import Screenshot from "@/Pages/Home/Partials/Screenshot.vue";
import Brands from "@/Pages/Home/Partials/Brands.vue";
import Integration from "@/Pages/Home/Partials/Integration.vue";
import Features from "@/Pages/Home/Partials/Features.vue";
import Testimonials from "@/Pages/Home/Partials/Testimonials.vue";
import Pricing from "@/Pages/Home/Partials/Pricing.vue";
import SelfHosted from "@/Pages/Home/Partials/SelfHosted.vue";
import Faq from "@/Pages/Home/Faq.vue";
import Bottom from "@/Pages/Home/Partials/Bottom.vue";

defineProps({
    plans: Array,
    banners: Array,
    discount: Number,
});
</script>

<template>
    <Gradient position="fixed" />

    <Banners :banners="banners" />

    <Header />

    <div class="min-h-screen relative">
        <Hero />

        <Screenshot />

        <Brands />

        <Suspense>
            <Integration />
        </Suspense>

        <Features />

        <Testimonials />

        <Pricing :plans="plans" />

        <SelfHosted :discount="discount" />

        <Faq />

        <Bottom />
    </div>
</template>
