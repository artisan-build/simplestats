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
                    <table class="w-full whitespace-nowrap text-sm">
                        <thead>
                            <tr class="text-left font-bold text-base">
                                <th class="p-3">{{ project.name }}</th>
                                <th class="w-1"></th>
                                <th class="text-center" colspan="8">Lifetime</th>
                                <th class="w-1"></th>
                                <th class="text-center" colspan="10">Yesterday</th>
                            </tr>
                            <tr class="text-right font-bold text-gray-600 bg-gray-100 border-t">
                                <th class="p-3 text-left">Instance</th>
                                <th class="w-1 bg-white border-y border-white"></th>
                                <th>Regs.</th>
                                <th>Reg. to DAU</th>
                                <th>Pay User</th>
                                <th>Reg. to Pay</th>
                                <th>Tot. Actives</th>
                                <th>Reg. to Actives</th>
                                <th>ARPU</th>
                                <th class="pr-2">ARPPU</th>
                                <th class="w-1 bg-white border-y border-white"></th>
                                <th>Regs.</th>
                                <th>NAU</th>
                                <th>DAU</th>
                                <th>DAPU</th>
                                <th>DAAPU</th>
                                <th>WAU</th>
                                <th>MAU</th>
                                <th>MAPU</th>
                                <th>MAAPU</th>
                                <th class="pr-2">Rev.</th>
                            </tr>
                        </thead>
                        <tbody>
                        <tr v-if="project.instances.length > 0" v-for="(instance, i) in project.instances" :key="instance.id"
                            class="hover:bg-gray-100 focus-within:bg-gray-50 text-gray-700 text-right border-t"
                            :class="{'bg-gray-50': i % 2 === 1}"
                        >
                            <td class="p-3 text-left">{{ instance.name }}</td>
                            <td class="w-1 bg-white border-y border-white"></td>
                            <td>{{ instance.stats.lifetime.REG }}</td>
                            <td>{{ instance.stats.lifetime.REG2DAU }}</td>
                            <td>{{ instance.stats.lifetime.PU }}</td>
                            <td>{{ instance.stats.lifetime.REG2PU }}</td>
                            <td>{{ instance.stats.lifetime.TAU }}</td>
                            <td>{{ instance.stats.lifetime.REG2TAU }}</td>
                            <td>{{ instance.stats.lifetime.ARPU }}</td>
                            <td class="pr-2">{{ instance.stats.lifetime.ARPPU }}</td>
                            <td class="w-1 bg-white border-y border-white"></td>
                            <td>{{ instance.stats.yesterday.REG }}</td>
                            <td>{{ instance.stats.yesterday.NAU }}</td>
                            <td>{{ instance.stats.yesterday.DAU }}</td>
                            <td>{{ instance.stats.yesterday.DAPU }}</td>
                            <td>{{ instance.stats.yesterday.DAAPU }}</td>
                            <td>{{ instance.stats.yesterday.WAU }}</td>
                            <td>{{ instance.stats.yesterday.MAU }}</td>
                            <td>{{ instance.stats.yesterday.MAPU }}</td>
                            <td>{{ instance.stats.yesterday.MAAPU }}</td>
                            <td class="pr-2">{{ instance.stats.yesterday.REV }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
