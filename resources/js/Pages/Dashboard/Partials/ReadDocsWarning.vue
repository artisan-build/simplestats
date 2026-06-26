<script setup>
import {Link} from '@inertiajs/vue3';
import SecondaryButton from "@/Components/SecondaryButton.vue";
import CloseIcon from 'bootstrap-icons/icons/x-circle.svg?component';

const appName = import.meta.env.VITE_APP_NAME;

const docsAction = (e) => {
    e.preventDefault();
    window.open('/docs');
}
</script>

<template>
    <div class="flex justify-between items-center px-5 py-3 bg-blue-500 text-white rounded-md mb-6">
        <div>
            To enable <strong>advanced tracking</strong> and for further instructions, check the setup guide in the documentation.
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
                        <CloseIcon class="size-6" />
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
