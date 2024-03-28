<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import {useForm} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Features from "@/Pages/Home/Partials/Features.vue";
import TwitterLink from "@/Components/TwitterLink.vue";
import Pricing from "@/Pages/Home/Partials/Pricing.vue";
import Screenshot from "@/Pages/Home/Partials/Screenshot.vue";

defineProps({
    plans: Array,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('early-subscription'), {
        onSuccess: () => form.reset('email'),
    });
};

const appName = import.meta.env.VITE_APP_NAME;

</script>

<template>
    <section class="w-full min-h-screen bg-white dark:bg-gray-900">
        <div class="container relative flex flex-col min-h-screen px-6 py-8 mx-auto">
            <nav class="flex items-center justify-between z-10">
                <div class="flex items-center justify-between">
                    <a href="/" class="inline-flex items-center">
                        <ApplicationLogo />
                    </a>
                </div>

                <div class="flex items-center gap-x-5 justify-between">
                    <div>
                        <a href="#features">Features</a>
                    </div>
                    <div>
                        <a href="#pricing">Pricing</a>
                    </div>
                    <form action="https://demo.simplestats.io/login" method="GET" target="_blank">
                        <SecondaryButton type="submit" class="relative">
                            Show Demo
                            <span class="flex absolute h-3 w-3 top-0 right-0 -mt-1 -mr-1">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-500"></span>
                            </span>
                        </SecondaryButton>
                    </form>
                    <TwitterLink />
                </div>
            </nav>

            <section class="mt-32">
                <h1 class="text-5xl font-extrabold text-center lg:text-7xl 2xl:text-8xl">
                    <span class="text-transparent bg-gradient-to-br bg-clip-text from-indigo-500 via-indigo-500 to-sky-500 dark:from-indigo-200 dark:via-indigo-300 dark:to-sky-500">
                        Coming
                    </span>

                    <span class="text-transparent bg-gradient-to-tr bg-clip-text from-indigo-500 via-pink-500 to-red-500 dark:from-sky-300 dark:via-pink-300 dark:to-red-500">
                        Soon
                    </span>
                </h1>

                <p class="max-w-3xl mx-auto mt-6 text-lg text-center text-gray-700 dark:text-white md:text-xl">
                    The missing statistics tool for your <strong>Laravel</strong> app.<br>
                    Get highly detailed user, revenue and campaign statistics in no time!
                </p>

                <div class="flex flex-col mt-8 space-y-3 sm:-mx-2 sm:flex-row sm:justify-center sm:space-y-0">
                    <form @submit.prevent="submit">
                        <div class="flex items-center">
                            <TextInput
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="block w-full h-10"
                                required
                                autofocus
                                placeholder="Email"
                            />

                            <PrimaryButton class="ml-4 h-10" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Notify&nbsp;Me
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <div class="text-center h-20 mt-3">
                    <span v-show="form.wasSuccessful" class="text-green-600 text-sm">
                        Thank you for subscribing!<br>We'll send you an email when the app is about to launch.
                    </span>
                    <InputError :message="form.errors.email" />
                </div>
            </section>

            <Screenshot />

            <Features />

            <Pricing :plans="plans" />
        </div>
    </section>
</template>
