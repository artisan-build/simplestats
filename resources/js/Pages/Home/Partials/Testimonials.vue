<script setup>
import SectionHeader from "@/Pages/Home/Partials/SectionHeader.vue";
import {snakeCase} from "lodash";

const testimonials = [
    {
        "name": "Marc Loskill",
        "title": "CEO",
        "company": "Heilpraktikerschule Likamundi",
        "link": "https://likamundi.de",
        "descr": "Integrating SimpleStats into our Laravel projects was incredibly easy. The seamless setup saved us hours, and now we can effortlessly monitor our campaigns and conversions. This tool has streamlined our workflow and provided invaluable insights and metrics."
    },
    {
        "name": "Filip Ganyicz",
        "title": "CTO",
        "company": "Finotive Group",
        "link": "https://www.finotivegroup.com",
        "descr": "SimpleStats has been a game-changer for us. Its backend analytics ensure pinpoint accuracy, giving us reliable data that isn’t affected by ad blockers. We now have a crystal-clear view of our payment and registration conversions. Highly recommended for anyone needing precise stats!"
    },
    {
        "name": "Ingmar Feldhoff",
        "title": "CEO",
        "company": "Trollgames GmbH",
        "link": "https://trollgames.de/?lang=en",
        "descr": "Thanks to SimpleStats, we can quickly see which of our campaigns are truly profitable. The detailed conversion tracking for payments and registrations has helped us optimize our marketing strategies without any extra effort. It’s an essential tool for any data-driven business."
    }
];
</script>

<template>
    <section class="flex justify-center">
        <div class="max-w-screen-md lg:max-w-screen-xl">
            <SectionHeader id="testimonials">
                Testimonials of our <span class="text-indigo-500">clients</span>
            </SectionHeader>

            <section class="grid grid-cols-1 gap-8 lg:grid-cols-2 xl:grid-cols-3">
                <div v-for="testimonial in testimonials" class="p-8 border rounded-lg dark:border-gray-700">
                    <p class="leading-loose text-gray-500 dark:text-gray-400">
                        {{ testimonial.descr }}
                    </p>

                    <div class="flex items-center mt-8 -mx-2">
                        <img class="object-cover mx-2 rounded-full w-14 shrink-0 h-14 ring-4 ring-gray-300 dark:ring-gray-700" :src="'/images/people/'+snakeCase(testimonial.name)+'.jpeg'" alt="">

                        <div class="mx-2">
                            <h1 class="font-semibold text-gray-800 dark:text-white">{{ testimonial.name }}</h1>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ testimonial.title }}, <a class="underline hover:text-indigo-600" :href="testimonial.link" target="_blank">{{ testimonial.company }}</a></span>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </section>
</template>

<style scoped>

</style>
