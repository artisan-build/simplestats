<script setup>
import Header from "@/Pages/Auth/Header.vue";
import Footer from "@/Components/Footer.vue";
import Testimonials from "@/Components/Testimonials.vue";
import {testimonials} from "@/Data/testimonials.js";
import {sample} from "lodash";
import Gradient from "@/Pages/Home/Partials/Gradient.vue";
import {onMounted, ref} from 'vue';

const selectedTestimonial = sample(testimonials);
const isVisible = ref(false);

onMounted(() => {
    setTimeout(() => {
        isVisible.value = true;
    }, 100);
});
</script>

<template>
    <div class="w-full min-h-screen flex bg-white dark:bg-dark-900">
        <div class="relative w-full md:w-1/2 flex items-center">
            <Gradient position="absolute" />
            <div class="animationElement relative mx-auto w-full sm:max-w-lg px-10 xl:px-0" :class="{ 'opacity-0 transform translate-y-8': !isVisible }">
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
                <Testimonials v-if="$page.props.appType === 'default'" :testimonials="[selectedTestimonial]" />
            </div>
        </div>
    </div>

    <Footer class="hidden" />
</template>

<style scoped>
.animationElement {
  transition: opacity 0.8s ease, transform 0.8s ease;
}
</style>
