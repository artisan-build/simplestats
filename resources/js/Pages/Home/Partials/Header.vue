<script setup>
import {Link} from "@inertiajs/vue3";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
</script>

<template>
    <div class="z-10 relative">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 pt-6">
            <Link href="/" class="inline-flex items-center">
                <ApplicationLogo />
            </Link>

            <div class="flex items-center gap-6">
                <a href="/#features" class="hidden lg:block">Features</a>
                <a href="/#pricing" class="hidden lg:block">Pricing</a>
                <a href="/#self-hosted" class="hidden lg:block">Self-Hosted</a>

                <a href="/docs" class="px-3">Docs</a>

                <Link v-if="$page.props.auth.user" :href="route('dashboard')" class=" text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">
                    Dashboard
                </Link>
                <template v-else>
                    <Link v-if="$page.props.canLogin" :href="route('login')" class="">Sign in</Link>

                    <Link v-if="$page.props.canRegister" :href="route('register')" class="border-indigo-700 hover:border-indigo-600 text-indigo-700 hover:text-indigo-600 hidden rounded-md border px-8 py-3 text-center font-bold md:inline-block">Get Started</Link>
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>
