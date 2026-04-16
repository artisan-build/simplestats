<script setup>
import {ref} from "vue";
import NavLink from "@/Components/NavLink.vue";
import {Link} from "@inertiajs/vue3";
import ApplicationIcon from "@/Components/ApplicationIcon.vue";
import ProfileDropdown from "@/Pages/Profile/Partials/ProfileDropdown.vue";
import FeedbackModal from "@/Components/FeedbackModal.vue";

const supportAction = (e) => {
    e.preventDefault();
    window.open('https://tawk.to/chat/6601f04ea0c6737bd124880f/1hprp5d8g');
}

const showFeedbackModal = ref(false);
</script>

<template>
    <div class="flex flex-col gap-y-1"  style="scrollbar-gutter: stable;">
        <div class="overflow-x-clip">
            <header class="py-4 text-center">
                <Link class="inline-block mt-2" :href="route('dashboard')">
                    <ApplicationIcon />
                </Link>
            </header>
        </div>
        <ul class="flex flex-col gap-y-3">
            <li class="flex flex-col gap-y-1">
                <NavLink :href="route('dashboard')" :active="route().current().startsWith('dashboard')">
                    <template #icon>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605" />
                        </svg>
                    </template>
                    Dashboard
                </NavLink>
            </li>
            <li class="flex flex-col gap-y-1">
                <NavLink :href="route('projects.index')" :active="route().current().startsWith('projects')">
                    <template #icon>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                        </svg>
                    </template>
                    Projects
                </NavLink>
            </li>
        </ul>
    </div>
    <div class="flex flex-col gap-y-1 mb-3">
        <ul class="flex flex-col gap-y-3">
            <li class="flex flex-col gap-y-1">
                <NavLink as="button" @click="showFeedbackModal = true">
                    <template #icon>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                        </svg>
                    </template>
                    Feedback
                </NavLink>
            </li>
            <li class="flex flex-col gap-y-1">
                <NavLink @click="supportAction">
                    <template #icon>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                        </svg>
                    </template>
                    Support
                </NavLink>
            </li>
            <li class="flex flex-col gap-y-1">
                <NavLink :href="route('teams.show', $page.props.auth.user.current_team)" :active="route().current().startsWith('teams')">
                    <template #icon>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                        </svg>
                    </template>
                    Team
                </NavLink>
            </li>
            <li class="flex flex-col gap-y-1" data-tour="profile-dropdown">
                <NavLink as="button" :active="route().t.routes[route().current()].uri.includes('user')">
                    <template #icon>
                        <ProfileDropdown direction="up" :triggerText="$page.props.auth.user.name" />
                    </template>
                </NavLink>
            </li>
        </ul>
    </div>
    <FeedbackModal :show="showFeedbackModal" @close="showFeedbackModal = false" />
</template>
