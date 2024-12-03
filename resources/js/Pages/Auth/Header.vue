<script setup>
import {Link} from "@inertiajs/vue3";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
</script>

<template>
    <Link href="/" class="inline-flex items-center">
        <img :src="$page.props.branding.app_logo" width="40" height="30">
    </Link>
</template>

<style scoped>

</style>
