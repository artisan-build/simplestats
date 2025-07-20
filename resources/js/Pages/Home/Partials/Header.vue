<script setup>
import {Link} from "@inertiajs/vue3";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import {onMounted, onUnmounted, ref} from 'vue';

const isVisible = ref(false);
const hasScrolled = ref(false);

const handleScroll = () => {
    hasScrolled.value = window.scrollY > 0;
};

onMounted(() => {
    setTimeout(() => {
        isVisible.value = true;
    }, 50); // Shorter delay for header to appear first

    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <div class="animationElement z-10 sticky top-0" :class="{ 'opacity-0': !isVisible, 'bg-white/90 dark:bg-dark-900/90 backdrop-blur-sm border-b border-gray-200/90 dark:border-dark-700/90 shadow-sm dark:shadow-dark-800/50': hasScrolled }">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-3">
            <Link href="/" class="inline-flex items-center">
                <ApplicationLogo />
            </Link>

            <div class="flex items-center gap-6">
                <a href="/#features" class="hidden lg:block hover:text-indigo-600">Features</a>
                <a href="/#pricing" class="hidden lg:block hover:text-indigo-600">Pricing</a>
                <a href="/#self-hosted" class="hidden lg:block hover:text-indigo-600">Self-Hosted</a>

                <a href="/docs" class="px-3 hover:text-indigo-600">Docs</a>

                <Link v-if="$page.props.auth.user" :href="route('dashboard')" class=" text-gray-600 hover:text-gray-900 dark:text-dark-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">
                    Dashboard
                </Link>
                <template v-else>
                    <Link v-if="$page.props.canLogin" :href="route('login')" class="hover:text-indigo-600">Sign in</Link>

                    <Link v-if="$page.props.canRegister" :href="route('register')" class=" text-white rounded-lg bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 dark:focus:ring-indigo-900 hidden px-5 py-1 text-center md:inline-block transition-all duration-200 hover:scale-105">
                        Get started
                    </Link>
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animationElement {
  transition: opacity 0.6s ease;
}
</style>
