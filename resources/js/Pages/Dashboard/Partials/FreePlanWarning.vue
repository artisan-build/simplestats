<script setup>
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { Link } from '@inertiajs/vue3';

const appName = import.meta.env.VITE_APP_NAME;
</script>

<template>
    <div class="flex justify-between items-center px-5 py-3 bg-blue-500 text-white rounded-md mb-6">
        <div>
            With the free plan of {{ appName }} you can only track a limited amount of users.
            <Link class="font-bold" :href="route('spark.portal')">Upgrade</Link> your plan to enable limitless tracking!
        </div>
        <div>
            <SecondaryButton @click="$inertia.visit(route('spark.portal'))">
                Upgrade now
            </SecondaryButton>
        </div>
    </div>
</template>

<style scoped>

</style>
