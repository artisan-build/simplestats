<script setup>
import {useForm} from "@inertiajs/vue3";
import InputLabel from "@/Components/InputLabel.vue";
import SidebarIcon from 'bootstrap-icons/icons/layout-sidebar.svg?component';
import SidebarInsetIcon from 'bootstrap-icons/icons/layout-sidebar-inset.svg?component';

const form = useForm({});

const submitToggle = () => {
    form.get(route('user-settings.toggle-menu'), {preserveScroll: true, only: ['auth']});
};
</script>


<template>
    <form @submit.prevent="submitToggle" class="hidden lg:block">
        <InputLabel class="hidden" for="menu" value="&nbsp;" />
        <button title="Toggle Sidebar" type="submit" class="text-gray-400 hover:text-gray-600 block p-3 pl-0">
            <SidebarIcon v-if="$page.props.auth.user.settings?.showMenu ?? true" class="size-6" />
            <SidebarInsetIcon v-else class="size-6" />
        </button>
        <span class="ml-2 hidden">Menu</span>
    </form>
</template>
