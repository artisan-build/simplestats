<script setup>
import {ref, watchEffect} from 'vue';
import {usePage} from '@inertiajs/vue3';
import CheckCircleIcon from 'bootstrap-icons/icons/check-circle.svg?component';
import ExclamationTriangleIcon from 'bootstrap-icons/icons/exclamation-triangle.svg?component';
import CloseIcon from 'bootstrap-icons/icons/x-lg.svg?component';

const page = usePage();
const show = ref(true);
const style = ref('success');
const message = ref('');

watchEffect(async () => {
    style.value = page.props.jetstream.flash?.bannerStyle || 'success';
    message.value = page.props.jetstream.flash?.banner || '';
    show.value = true;
    setTimeout(() => {show.value = false;}, 5000);
});
</script>

<template>
    <div>
        <div v-if="show && message" class="fixed top-3 right-3 rounded-md shadow-md z-100 w-80" :class="{ 'bg-green-500': style == 'success', 'bg-red-700': style == 'danger' }">
            <div class="max-w-screen-xl mx-auto py-2 px-3">
                <div class="flex items-center justify-between flex-wrap">
                    <div class="w-0 flex-1 flex items-center min-w-0">
                        <span class="flex p-2 rounded-lg" :class="{ 'bg-green-600': style == 'success', 'bg-red-600': style == 'danger' }">
                            <CheckCircleIcon v-if="style == 'success'" class="size-5 text-white" />

                            <ExclamationTriangleIcon v-if="style == 'danger'" class="size-5 text-white" />
                        </span>

                        <p class="ms-3 font-medium text-sm text-white">
                            {{ message }}
                        </p>
                    </div>

                    <div class="shrink-0 sm:ms-3">
                        <button
                            type="button"
                            class="-me-1 flex p-2 rounded-md focus:outline-none sm:-me-2 transition"
                            :class="{ 'hover:bg-green-600 focus:bg-green-600': style == 'success', 'hover:bg-red-600 focus:bg-red-600': style == 'danger' }"
                            aria-label="Dismiss"
                            @click.prevent="show = false"
                        >
                            <CloseIcon class="size-5 text-white" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
