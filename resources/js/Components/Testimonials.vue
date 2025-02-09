<script setup>
import {snakeCase} from "lodash";

defineProps({
    testimonials: Array,
})
</script>

<template>
    <div v-for="testimonial in testimonials">
        <div class="bg-white dark:bg-gray-800 p-8 border shadow-md border-gray-200 rounded-lg dark:border-gray-700  break-inside-avoid mb-6">
            <p class="leading-loose text-sm text-gray-500 dark:text-gray-400">
                {{ testimonial.descr }}
            </p>

            <div class="flex items-center mt-8 -mx-2">
                <img class="object-cover mx-2 rounded-full w-14 shrink-0 h-14 ring-4 ring-gray-300 dark:ring-gray-700" :src="'/images/people/'+snakeCase(testimonial.name)+'.jpeg'" :alt="testimonial.name" loading="lazy">

                <div class="mx-2">
                    <div class="font-semibold text-gray-800 dark:text-white">
                        <a v-if="testimonial.social_link" :href="testimonial.social_link" target="_blank" class="underline hover:text-indigo-600">
                            {{ testimonial.name }}
                        </a>
                        <span v-else>
                            {{ testimonial.name }}
                        </span>
                    </div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ testimonial.title }} of <a class="underline hover:text-indigo-600" :href="testimonial.company_link" target="_blank">{{ testimonial.company }}</a></span>
                </div>
            </div>
        </div>
    </div>
</template>
