<script setup>
import {useForm} from "@inertiajs/vue3";
import InputLabel from "@/Components/InputLabel.vue";

const form = useForm({});

const submitToggle = () => {
    form.get(route('menu.toggle'), {preserveScroll: true, only: ['auth']});
};
</script>


<template>
    <form @submit.prevent="submitToggle" class="hidden lg:block">
        <InputLabel class="hidden" for="menu" value="&nbsp;" />
        <button type="submit" class="text-gray-400 hover:text-gray-600 block p-3 pl-0">
            <svg v-if="$page.props.auth.user.settings?.showMenu ?? true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="m18.75 4.5-7.5 7.5 7.5 7.5m-6-15L5.25 12l7.5 7.5" />
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
            </svg>
        </button>
        <span class="ml-2 hidden">Menu</span>
    </form>
</template>
