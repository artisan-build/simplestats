<script setup>
import Header from "@/Pages/Auth/Header.vue";
import Footer from "@/Components/Footer.vue";
import Testimonials from "@/Components/Testimonials.vue";
import {testimonials} from "@/Data/testimonials.js";
import {sample} from "lodash";

const selectedTestimonial = sample(testimonials);
</script>

<template>
    <div class="w-full min-h-screen flex bg-white dark:bg-gray-900">
        <div class="w-full md:w-1/2 flex items-center">
            <div class="mx-auto w-full sm:max-w-lg px-10 xl:px-0">
                <div class="text-center">
                    <Header class="mb-10" />

                    <h2 class="text-3xl font-medium mb-10">
                        <slot name="title" />
                    </h2>
                </div>
                <slot />
            </div>
        </div>
        <div class="w-1/2 hidden md:flex items-center border-l border-gray-400" style="background: url('/images/side_bg.jpg'); background-size: cover;">
            <div class="mx-auto w-full sm:max-w-lg px-10 xl:px-0">
                <Testimonials :testimonials="[selectedTestimonial]" />
            </div>
        </div>
    </div>

    <Footer class="hidden" />
</template>
