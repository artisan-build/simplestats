<script setup>
import {Link} from "@inertiajs/vue3";


defineProps({
    instances: Array,
});
</script>

<template>
    <tr class="text-left font-bold">
        <th class="pb-4 pt-6 px-6">Instance</th>
        <th v-if="instances[0].project" class="pb-4 pt-6 px-6">Project</th>
    </tr>
    <tr v-for="instance in instances" :key="instance.id" class="hover:bg-gray-50 focus-within:bg-gray-50 text-gray-700">
        <td class="border-t">
            <Link class="flex items-center px-6 py-4 focus:text-indigo-500" :href="route('instances.show', instance.id)">
                {{ instance.name }}
            </Link>
        </td>

        <td v-if="instance.project" class="border-t">
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
</template>
