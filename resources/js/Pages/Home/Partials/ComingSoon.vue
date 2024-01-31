<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import {useForm} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";


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
                        <span class="ml-3 inline-flex text-xl font-bold leading-5 tracking-tight text-gray-950 dark:text-white">
                            {{ appName }}
                        </span>
                    </a>
                </div>

                <div class="flex items-center justify-between">
                    <form action="https://demo.simplestats.io/login" method="GET" target="_blank">
                        <SecondaryButton type="submit" class="mr-5 relative">
                            Show Demo
                            <span class="flex absolute h-3 w-3 top-0 right-0 -mt-1 -mr-1">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-500"></span>
                            </span>
                        </SecondaryButton>
                    </form>
                    <a href="https://twitter.com/SimpleStats_io" target="_blank" class="inline-block text-white bg-black rounded-full p-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-twitter-x" viewBox="0 0 16 16">
                            <path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865z"/>
                        </svg>
                    </a>
                </div>
            </nav>

            <section class="flex items-center flex-1">
                <div class="flex flex-col w-full ">
                    <h1 class="text-5xl font-extrabold text-center lg:text-7xl 2xl:text-8xl">
                        <span class="text-transparent bg-gradient-to-br bg-clip-text from-indigo-500 via-indigo-500 to-sky-500 dark:from-indigo-200 dark:via-indigo-300 dark:to-sky-500">
                            Coming
                        </span>

                        <span class="text-transparent bg-gradient-to-tr bg-clip-text from-indigo-500 via-pink-500 to-red-500 dark:from-sky-300 dark:via-pink-300 dark:to-red-500">
                            Soon
                        </span>
                    </h1>

                    <p class="max-w-3xl mx-auto mt-6 text-lg text-center text-gray-700 dark:text-white md:text-xl">
                        The missing statistic tool for <strong>Laravel</strong> apps.<br>Get highly detailed user, payment and campaign statistics in no time!
                    </p>
                    <p class="max-w-3xl mx-auto mt-5 text-sm text-gray-500 text-center">
                        (For Laravel apps the setup will be super simple, but the API will basically work for every client!)
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

                                <PrimaryButton class="ml-4 h-10 bg-indigo-500 hover:bg-indigo-600 focus:bg-indigo-600" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
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
                </div>
            </section>

            <footer v-if="false" class="flex flex-col items-center mt-12 sm:flex-row sm:justify-between">
                <a href="#" class="text-gray-700 transition-colors duration-300 dark:text-gray-300 hover:text-indigo-500 dark:hover:text-indigo-400"> Privacy Policy </a>

                <div class="mt-4 -mx-4 md:mt-0">
                    <a href="#" class="px-4 text-gray-700 transition-colors duration-300 dark:text-gray-300 hover:text-indigo-500 dark:hover:text-indigo-400"> Facebook</a>

                    <a href="#" class="px-4 text-gray-700 transition-colors duration-300 dark:text-gray-300 hover:text-indigo-500 dark:hover:text-indigo-400">Instagram</a>

                    <a href="#" class="px-4 text-gray-700 transition-colors duration-300 dark:text-gray-300 hover:text-indigo-500 dark:hover:text-indigo-400"> LinkedIn</a>
                </div>
            </footer>
        </div>
    </section>
</template>
