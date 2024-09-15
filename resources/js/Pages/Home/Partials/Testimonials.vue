<script setup>
import SectionHeader from "@/Pages/Home/Partials/SectionHeader.vue";
import Testimonials from "@/Components/Testimonials.vue";
import {testimonials} from "@/Data/testimonials.js";

const topTestimonials = testimonials.slice(0, 3);
</script>

<template>
    <section class="flex justify-center">
        <div class="max-w-screen-md lg:max-w-screen-xl">
            <SectionHeader id="testimonials">
                Testimonials of our <span class="text-indigo-500">clients</span>
            </SectionHeader>

            <section class="grid grid-cols-1 gap-8 lg:grid-cols-3 px-6">
                <Testimonials :testimonials="topTestimonials" />
            </section>
        </div>
    </section>
</template>

<style scoped>

</style>
