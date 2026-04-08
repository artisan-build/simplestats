<script setup>
import SectionHeader from "@/Pages/Home/Partials/SectionHeader.vue";
import Testimonials from "@/Components/Testimonials.vue";
import {testimonials} from "@/Data/testimonials.js";

const testimonialsData = testimonials;//.sort(() => Math.random() - 0.5);
</script>

<template>
    <section class="flex justify-center">
        <div class="max-w-screen-md lg:max-w-screen-xl">
            <SectionHeader id="testimonials">
                {{ $t('Testimonials of our') }} <span class="text-indigo-500 underline">{{ $t('fans') }}</span>
            </SectionHeader>

            <div class="gap-6 columns-1 md:columns-2 xl:columns-3 px-6">
                <Testimonials :testimonials="testimonialsData" />
            </div>
        </div>
    </section>
</template>

<style scoped>

</style>
