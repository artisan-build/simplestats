<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import {Link} from "@inertiajs/vue3";

defineProps({
    projects: Object,
});
</script>

<template>
    <AppLayout title="Dashboard" fullscreen=true>
        <template #header>
            Dashboard
        </template>

        <div>
            <div v-for="project in projects">
                <div class="bg-white dark:bg-gray-800 rounded-md shadow overflow-x-auto mb-8 border">
                    <h2 class="text-left font-bold py-4 px-3 text-lg">{{ project.name }}</h2>
                    <!-- Instance -->
                    <table class="w-full whitespace-nowrap">
                        <thead>
                            <tr class="text-left font-bold">
                                <th class="p-3 border-t">&nbsp;</th>
                                <th class="w-1"></th>
                                <th class="p-3 border-t">Lifetime</th>
                                <th class="w-1"></th>
                                <th class="p-3 border-t">Yesterday</th>
                                <th class="w-1"></th>
                            </tr>
                            <tr class="text-left font-bold">
                                <th class="p-3 bg-gray-50 border-t">Instance</th>
                                <th class="w-1"></th>
                                <th class="p-3 bg-gray-50 border-t">Regs.</th>
                                <th class="w-1"></th>
                                <th class="p-3 bg-gray-50 border-t">Regs.</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                        <tr v-if="project.instances.length > 0" v-for="instance in project.instances" :key="instance.id" class="hover:bg-gray-50 focus-within:bg-gray-50 text-gray-700">
                            <td class="border-t">
                                <Link class="flex items-center p-3 focus:text-indigo-500" :href="route('instances.show', instance.id)">
                                    {{ instance.name }}
                                </Link>
                            </td>
                            <td class="w-1"></td>
                            <td class="border-t">
                                <Link class="flex items-center p-3 focus:text-indigo-500" :href="route('instances.show', instance.id)">
                                    {{ instance.statsusers_count }}
                                </Link>
                            </td>
                            <td class="w-1"></td>
                            <td class="border-t">
                                <Link class="flex items-center p-3 focus:text-indigo-500" :href="route('instances.show', instance.id)">
                                    {{ instance.statsusers_count_yesterday }}
                                </Link>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
