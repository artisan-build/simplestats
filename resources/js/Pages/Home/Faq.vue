<script setup>
import SectionHeader from "@/Pages/Home/Partials/SectionHeader.vue";
import {ref} from "vue";

const questions = ref([
    {
        'title': 'Why is tracking not working even though everything is set up correctly?',
        'answer': 'Most often, this is a queue-related issue. Ensure you have cleared your config cache and restarted your queue after adding the API token to your .env file.',
        'active': false,
    },
    {
        'title': 'Are the limits for different plans applied monthly or as a total?',
        'answer': 'The limits are total values. Once you reach 70% of your quota, we’ll prompt you to upgrade your plan. If you don’t, we will stop storing new data as soon as you reach 100%.',
        'active': false,
    },
    {
        'title': 'How are unique visitors tracked?',
        'answer': 'We track unique visitors in a GDPR-compliant way by generating a unique visitor hash each time a new visitor accesses your site. This hash is derived from a combination of the visitor’s User-Agent, IP address, and a daily salt. This method enables unique visitor tracking without storing personally identifiable information such as cookies, ensuring privacy and data protection.',
        'active': false,
    },
    {
        'title': 'Do you support custom events?',
        'answer': 'Not yet, but it’s on our roadmap!',
        'active': false,
    },
]);

const toggleQuestion = (index) => {
    questions.value[index].active = ! questions.value[index].active;
};
</script>

<template>
    <section class="pb-32 bg-white dark:bg-gray-900">
        <div class="container max-w-4xl px-6 py-10 mx-auto">

            <SectionHeader id="self-hosted">
                Frequently asked&nbsp;<span class="text-indigo-500">questions</span>
            </SectionHeader>

            <div class="space-y-8">
                <div v-for="(question, index) in questions" class="border-2 border-gray-100 rounded-lg dark:border-gray-700">
                    <button @click="toggleQuestion(index)" class="flex items-center justify-between w-full p-8">
                        <h3 class="font-semibold text-gray-700 dark:text-white">{{ question.title }}</h3>

                        <span v-if="question.active" class="text-gray-400 bg-gray-200 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6" />
                            </svg>
                        </span>
                        <span v-else class="text-white bg-indigo-500 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                        </span>
                    </button>

                    <div v-show="question.active">
                        <hr class="border-gray-200 dark:border-gray-700">

                        <p class="p-8 text-gray-500 dark:text-gray-300">
                            {{ question.answer }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
