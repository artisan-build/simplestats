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
                    <table class="w-full whitespace-nowrap">
                        <thead>
                            <tr class="text-left font-bold">
                                <th colspan="2" class="py-4 px-3 text-lg">{{ project.name }}</th>
                            </tr>
                            <tr class="text-left font-bold bg-gray-50">
                                <th class="p-3 border-t">Instance</th>
                                <th class="p-3 border-t">Regs.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="project.instances.length > 0" v-for="instance in project.instances" :key="instance.id" class="hover:bg-gray-50 focus-within:bg-gray-50 text-gray-700">
                                <td class="border-t">
                                    <Link class="flex items-center p-3 focus:text-indigo-500" :href="route('instances.show', instance.id)">
                                        {{ instance.name }}
                                    </Link>
                                </td>
                                <td class="border-t">
                                    <Link class="flex items-center p-3 focus:text-indigo-500" :href="route('instances.show', instance.id)">
                                        {{ instance.statsusers_count }}
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
