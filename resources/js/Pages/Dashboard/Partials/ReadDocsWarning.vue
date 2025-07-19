<script setup>
import {Link} from '@inertiajs/vue3';
import SecondaryButton from "@/Components/SecondaryButton.vue";

const appName = import.meta.env.VITE_APP_NAME;

const docsAction = (e) => {
    e.preventDefault();
    window.open('/docs');
}
</script>

<template>
    <div class="flex justify-between items-center px-5 py-3 bg-blue-500 text-white rounded-md mb-6">
        <div>
            To enable <strong>tracking</strong> and for further instructions, check the setup guide in the documentation.
        </div>
        <div>
            <div class="flex items-center gap-2">
                <div>
                    <SecondaryButton @click="docsAction">
                        Open Docs
                    </SecondaryButton>
                </div>
                <div>
                    <Link :href="route('stats.hide-read-docs-warning')" method="post" class="hover:text-gray-300">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
