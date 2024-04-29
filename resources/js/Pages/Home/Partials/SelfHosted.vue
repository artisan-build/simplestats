<script setup>
import SectionHeader from "@/Pages/Home/Partials/SectionHeader.vue";

const appName = import.meta.env.VITE_APP_NAME;
</script>

<template>
    <section class="mb-32 flex justify-center">
        <div class="max-w-screen-xl">
            <SectionHeader id="self-hosted">
                Take full control with&nbsp;<span class="text-indigo-500">Self-Hosted</span>&nbsp;
            </SectionHeader>

            <div class="text-center -mt-10 max-w-2xl m-auto">
                <p class="leading-normal text-gray-600 text-lg">
                    Embrace the ultimate level of autonomy and security with our Self-Hosted solution.
                    Ideal for those who demand full control.
                    This option allows you to host our powerful statistics tool on your own infrastructure,
                    ensuring complete data sovereignty and customizable security.
                </p>

                <p class="mt-10 leading-normal text-gray-600 text-lg">
                    Start <span class="italic">{{ appName }} Self-Hosted</span> today and take full control!
                </p>

                <div class="inline-flex w-full mt-10 sm:w-auto">
                    <a href="mailto:simplestats@laracraft.tech" class="block text-center w-full px-4 py-2 font-medium tracking-wide text-white capitalize transition-colors duration-300 transform bg-indigo-500 rounded-md hover:bg-indigo-600 focus:outline-none focus:bg-indigo-600">
                        Contact Sales
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>
