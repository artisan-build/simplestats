<script setup>
import {Link, router} from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";


defineProps({
    instances: Array,
    insideProject: [Array, null]
});
</script>

<template>
    <tr class="text-left font-bold">
        <th class="pb-4 pt-6 px-6">Instance</th>
        <th v-if="! insideProject" class="pb-4 pt-6 px-6">Project</th>
    </tr>
    <tr v-if="instances.length > 0" v-for="instance in instances" :key="instance.id" class="hover:bg-gray-50 focus-within:bg-gray-50 text-gray-700">
        <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="route('instances.show', instance.id)">
                {{ instance.name }}
            </Link>
        </td>

        <td v-if="! insideProject" class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="route('instances.show', instance.id)">
                {{ instance.project.name }}
            </Link>
        </td>

        <td class="w-px border-t">
            <Link class="flex items-center px-4" :href="route('instances.show', instance.id)">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </Link>
        </td>
    </tr>
    <tr v-else>
        <td class="border-t" colspan="2">
            <div class="px-6 py-4">
                No Instance created!
            </div>
        </td>
    </tr>
    <tr v-if="insideProject">
        <td class="border-t" colspan="2">
            <div class="flex flex-row-reverse px-6 py-4 bg-gray-50 dark:bg-gray-800">
                <PrimaryButton type="button" @click="router.visit(route('instances.index', {selected_project_id: insideProject.id}))">
                    Create New Instance
                </PrimaryButton>
            </div>
        </td>
    </tr>
</template>
