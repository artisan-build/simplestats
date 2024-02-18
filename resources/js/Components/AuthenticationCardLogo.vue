<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
</script>

<template>
    <Link class="hidden" :href="'/'">
        <ApplicationLogo />
    </Link>
</template>
