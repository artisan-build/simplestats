<script setup>
import {Link, router} from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";


defineProps({
    instances: Array,
    insideProject: [Object, null]
});
</script>

<template>
    <tr class="text-left font-bold">
        <th class="pb-4 pt-6 px-6">Your existing Instances</th>
        <th v-if="! insideProject" class="pb-4 pt-6 px-6">Project</th>
    </tr>
    <tr v-if="instances.length > 0" v-for="instance in instances" :key="instance.id" class="hover:bg-gray-50 focus-within:bg-gray-50 text-gray-700">
        <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="route('projects.instances.show', [instance.project_id, instance.id])">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="inline-block w-5 h-5 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 17.25v-.228a4.5 4.5 0 0 0-.12-1.03l-2.268-9.64a3.375 3.375 0 0 0-3.285-2.602H7.923a3.375 3.375 0 0 0-3.285 2.602l-2.268 9.64a4.5 4.5 0 0 0-.12 1.03v.228m19.5 0a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3m19.5 0a3 3 0 0 0-3-3H5.25a3 3 0 0 0-3 3m16.5 0h.008v.008h-.008v-.008Zm-3 0h.008v.008h-.008v-.008Z" />
                </svg>
                {{ instance.name }}
            </Link>
        </td>

        <td v-if="! insideProject" class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="route('projects.instances.show', [instance.project_id, instance.id])">
                {{ instance.project.name }}
            </Link>
        </td>

        <td class="w-px border-t">
            <Link class="flex items-center px-4" :href="route('projects.instances.show', [instance.project_id, instance.id])">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </Link>
        </td>
    </tr>
    <tr v-else>
        <td class="border-t" colspan="2">
            <div class="px-6 py-4 text-red-600 text-sm">
                No Instance has been created for this Project yet!
            </div>
        </td>
    </tr>
</template>
