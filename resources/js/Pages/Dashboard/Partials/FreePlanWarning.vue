<script setup>
import SecondaryButton from "@/Components/SecondaryButton.vue";
import {Link} from '@inertiajs/vue3';
import CloseIcon from 'bootstrap-icons/icons/x-circle.svg?component';

const appName = import.meta.env.VITE_APP_NAME;
</script>

<template>
    <div class="flex justify-between items-center px-5 py-3 bg-blue-500 text-white rounded-md mb-6">
        <div>
            With the <a href="/#pricing" target="_blank" class="font-bold underline">free plan</a> of {{ appName }} you can only track a limited amount of data.
            <Link class="font-bold underline" :href="route('spark.portal')">Upgrade</Link> to unlock the full power of {{ appName }}!
        </div>
        <div>
            <div class="flex items-center gap-2">
                <div>
                    <SecondaryButton @click="$inertia.visit(route('spark.portal'))">
                        Upgrade now
                    </SecondaryButton>
                </div>
                <div>
                    <Link :href="route('stats.hide-free-plan-warning')" method="post" class="hover:text-gray-300">
                        <CloseIcon class="size-6" />
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
