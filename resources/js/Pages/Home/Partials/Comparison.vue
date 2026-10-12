<script setup>
import CheckCircleIcon from "bootstrap-icons/icons/check-circle-fill.svg?component";
import MinusCircleIcon from "bootstrap-icons/icons/dash-circle-fill.svg?component";
import XCircleIcon from "bootstrap-icons/icons/x-circle-fill.svg?component";
import SectionHeader from "@/Pages/Home/Partials/SectionHeader.vue";
import {comparisonFeatures, competitors} from "@/Data/comparison.js";
</script>

<template>
    <section class="flex justify-center">
        <div class="max-w-screen-md lg:max-w-screen-xl w-full">
            <SectionHeader id="comparison" :eyebrow="$t('Why us')">
                {{ $t('How we') }}&nbsp;<span class="text-indigo-500 underline">{{ $t('compare') }}</span>
            </SectionHeader>

            <!-- Desktop Table -->
            <div class="hidden md:block px-6 overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-dark-700">
                            <th class="py-4 px-4 text-sm font-semibold text-gray-500 dark:text-dark-400"></th>
                            <th v-for="competitor in competitors"
                                :key="competitor.key"
                                class="py-4 px-4 text-center text-sm font-semibold"
                                :class="competitor.key === 'simplestats'
                                    ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/10 rounded-t-lg'
                                    : 'text-gray-700 dark:text-dark-300'"
                            >
                                {{ competitor.name }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(feature, index) in comparisonFeatures"
                            :key="index"
                            class="border-b border-gray-200 dark:border-dark-700 last:border-0"
                        >
                            <td class="py-4 px-4 text-sm font-medium text-gray-700 dark:text-dark-300">
                                {{ $t(feature.label) }}
                            </td>
                            <td v-for="competitor in competitors"
                                :key="competitor.key"
                                class="py-4 px-4 text-center"
                                :class="competitor.key === 'simplestats' ? 'bg-indigo-50 dark:bg-indigo-900/10' : ''"
                            >
                                <CheckCircleIcon v-if="feature[competitor.key] === true" class="size-5 text-indigo-500 mx-auto" />
                                <MinusCircleIcon v-else-if="feature[competitor.key] === 'partial'" class="size-5 text-indigo-300 dark:text-indigo-500/40 mx-auto" />
                                <XCircleIcon v-else class="size-5 text-gray-300 dark:text-dark-600 mx-auto" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="md:hidden px-6 space-y-4">
                <div v-for="competitor in competitors"
                     :key="competitor.key"
                     class="rounded-lg border p-5"
                     :class="competitor.key === 'simplestats'
                         ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-50 dark:bg-indigo-900/10'
                         : 'border-gray-200 dark:border-dark-700'"
                >
                    <h3 class="font-bold text-lg mb-3"
                        :class="competitor.key === 'simplestats'
                            ? 'text-indigo-600 dark:text-indigo-400'
                            : 'text-gray-800 dark:text-white'"
                    >
                        {{ competitor.name }}
                    </h3>
                    <ul class="space-y-2">
                        <li v-for="(feature, index) in comparisonFeatures"
                            :key="index"
                            class="flex items-center gap-2"
                        >
                            <CheckCircleIcon v-if="feature[competitor.key] === true" class="w-5 h-5 text-indigo-500 shrink-0" />
                            <MinusCircleIcon v-else-if="feature[competitor.key] === 'partial'" class="w-5 h-5 text-indigo-300 dark:text-indigo-500/40 shrink-0" />
                            <XCircleIcon v-else class="w-5 h-5 text-gray-300 dark:text-dark-600 shrink-0" />
                            <span class="text-sm text-gray-700 dark:text-dark-300">{{ $t(feature.label) }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
</template>
