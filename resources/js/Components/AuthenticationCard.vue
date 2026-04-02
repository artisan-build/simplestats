<script setup>
import Header from "@/Pages/Auth/Header.vue";
import Footer from "@/Components/Footer.vue";
import Testimonials from "@/Components/Testimonials.vue";
import {testimonials} from "@/Data/testimonials.js";
import {sample} from "@/Helpers/StringHelpers.js";
import Gradient from "@/Pages/Home/Partials/Gradient.vue";
const selectedTestimonial = sample(testimonials);

const appType = import.meta.env.VITE_APP_TYPE;
</script>

<template>
    <div class="w-full min-h-screen flex bg-white dark:bg-dark-900">
        <div class="relative w-full md:w-1/2 flex items-center">
            <Gradient v-if="appType === 'saas'" position="absolute" />
            <div class="animationElement relative mx-auto w-full sm:max-w-lg px-10 xl:px-0">
                <div class="text-center">
                    <Header class="mb-10" />

                    <h2 class="text-3xl font-medium mb-10">
                        <slot name="title" />
                    </h2>
                </div>
                <slot />
            </div>
        </div>
        <div class="w-1/2 hidden md:flex items-center" :style="`background: url('${$page.props.branding.app_background}'); background-size: cover;`">
            <div class="mx-auto w-full sm:max-w-lg px-10 xl:px-0">
                <Testimonials v-if="appType === 'saas'" :testimonials="[selectedTestimonial]" />
            </div>
        </div>
    </div>

    <Footer class="hidden" />
</template>

<style scoped>
.animationElement {
  animation: fadeInUp 0.8s ease forwards;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(2rem);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
