<script setup>
import {ref} from "vue";
import NavLink from "@/Components/NavLink.vue";
import {Link} from "@inertiajs/vue3";
import ApplicationIcon from "@/Components/ApplicationIcon.vue";
import ProfileDropdown from "@/Pages/Profile/Partials/ProfileDropdown.vue";
import FeedbackModal from "@/Components/FeedbackModal.vue";
import {
    ArrowTrendingUpIcon,
    ChartPieIcon,
    FolderIcon,
    LightBulbIcon,
    PresentationChartLineIcon,
    QuestionMarkCircleIcon,
    RectangleGroupIcon,
    UserGroupIcon,
} from "@heroicons/vue/24/outline";

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
        <ul class="flex flex-col gap-y-3 text-center">
            <li class="flex flex-col gap-y-1">
                <NavLink :href="route('dashboard')" :active="route().current().startsWith('dashboard')">
                    <template #icon>
                        <PresentationChartLineIcon class="size-6" />
                    </template>
                    Dashboard
                </NavLink>
            </li>
            <li v-if="$page.props.teamTracksUsers" class="flex flex-col gap-y-1">
                <NavLink :href="route('retention')" :active="route().current().startsWith('retention')">
                    <template #icon>
                        <RectangleGroupIcon class="size-6" />
                    </template>
                    Retention
                </NavLink>
            </li>
            <li v-if="$page.props.teamTracksRecurring" class="flex flex-col gap-y-1">
                <NavLink :href="route('recurring-revenue')" :active="route().current().startsWith('recurring-revenue')">
                    <template #icon>
                        <ArrowTrendingUpIcon class="size-6" />
                    </template>
                    Subscriptions
                </NavLink>
            </li>
            <li v-if="$page.props.teamTracksPayments" class="flex flex-col gap-y-1">
                <NavLink :href="route('campaigns')" :active="route().current().startsWith('campaigns')">
                    <template #icon>
                        <ChartPieIcon class="size-6" />
                    </template>
                    Campaigns
                </NavLink>
            </li>
            <li class="flex flex-col gap-y-1">
                <NavLink :href="route('projects.index')" :active="route().current().startsWith('projects')">
                    <template #icon>
                        <FolderIcon class="size-6" />
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
                        <LightBulbIcon class="size-6" />
                    </template>
                    Feedback
                </NavLink>
            </li>
            <li class="flex flex-col gap-y-1">
                <NavLink @click="supportAction">
                    <template #icon>
                        <QuestionMarkCircleIcon class="size-6" />
                    </template>
                    Support
                </NavLink>
            </li>
            <li class="flex flex-col gap-y-1">
                <NavLink :href="route('teams.show', $page.props.auth.user.current_team)" :active="route().current().startsWith('teams')">
                    <template #icon>
                        <UserGroupIcon class="size-6" />
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
