<script setup>
import StatsCols from "@/Pages/Overview/Partials/StatsCols.vue";
import {Link} from "@inertiajs/vue3";

const props = defineProps({
    showGroupTotal: Boolean,
    statsGroup: Object,
});

</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-md shadow overflow-x-auto mb-8 border p-1">
        <table class="w-full whitespace-nowrap text-sm">
            <thead>
            <tr class="text-left font-bold text-base">
                <th class="p-3">
                    <Link :href="route('dashboard', (statsGroup.project_id) ? {projectId: statsGroup.project_id} : {})" class="hover:underline">
                        {{ statsGroup.name }}
                    </Link>
                </th>
                <th class="w-1"></th>
                <th class="text-center" colspan="8">Lifetime</th>
                <th class="w-1"></th>
                <th class="text-center" colspan="10">Yesterday</th>
            </tr>
            <tr class="text-center font-bold text-gray-600 bg-gray-100">
                <th class="p-3 text-left">Instance</th>
                <th class="w-1 bg-white"></th>
                <th class="border-r border-gray-200 pr-1">Regs.</th>
                <th class="border-r border-gray-200 pr-1">Reg. to DAU</th>
                <th class="border-r border-gray-200 pr-1">Pay User</th>
                <th class="border-r border-gray-200 pr-1">Reg. to Pay</th>
                <th class="border-r border-gray-200 pr-1">Tot. Actives</th>
                <th class="border-r border-gray-200 pr-1">Reg. to Actives</th>
                <th class="border-r border-gray-200 pr-1">ARPU</th>
                <th class="border-r border-gray-200 pr-1">ARPPU</th>
                <th class="w-1 bg-white"></th>
                <th class="border-r border-gray-200 pr-1">Regs.</th>
                <th class="border-r border-gray-200 pr-1">NAU</th>
                <th class="border-r border-gray-200 pr-1">DAU</th>
                <th class="border-r border-gray-200 pr-1">DAPU</th>
                <th class="border-r border-gray-200 pr-1">DAAPU</th>
                <th class="border-r border-gray-200 pr-1">WAU</th>
                <th class="border-r border-gray-200 pr-1">MAU</th>
                <th class="border-r border-gray-200 pr-1">MAPU</th>
                <th class="border-r border-gray-200 pr-1">MAAPU</th>
                <th class="pr-2">Rev. (N)</th>
            </tr>
            </thead>
            <tbody>
            <tr v-if="statsGroup.instances && statsGroup.instances.length > 0" v-for="(instance, i) in statsGroup.instances" :key="instance.instance_id"
                class="hover:bg-gray-100 focus-within:bg-gray-50 text-gray-700 text-right"
                :class="{'bg-gray-50': i % 2 === 1}"
            >
                <StatsCols :stats="instance.stats" :projectId="instance.project_id" :instanceId="instance.instance_id" />
            </tr>
            <tr v-else-if="statsGroup.project_id !== 0">
                <td class="p-3 text-base text-red-600" colspan="10">
                    No Instance configured for this Project!
                    Go <Link class="font-bold hover:underline" :href="route('projects.show', statsGroup.project_id)">here</Link> to add one.
                </td>
            </tr>
            <tr v-if="showGroupTotal" class="hover:bg-gray-100 focus-within:bg-gray-50 text-gray-700 text-right border-t">
                <StatsCols :stats="statsGroup.stats" :projectId="statsGroup.project_id" />
            </tr>
            </tbody>
        </table>
    </div>
</template>

<style scoped>

</style>
