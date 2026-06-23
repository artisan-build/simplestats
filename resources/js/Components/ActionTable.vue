<script setup>
import SectionTitle from './SectionTitle.vue';
import Pagination from "@/Components/Pagination.vue";

const props = defineProps({
    links: Array,
    maxWidth: {type: String, default: 'max-w-3xl'},
});
</script>

<template>
    <div :class="maxWidth" class="mx-auto">
        <SectionTitle>
            <template #title>
                <slot name="title" />
            </template>
            <template #description>
                <slot name="description" />
            </template>
            <template #aside>
                <slot name="aside" />
            </template>
        </SectionTitle>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="bg-white dark:bg-dark-800 border rounded-md shadow overflow-x-auto">
                <table class="w-full whitespace-nowrap">
                    <slot name="content" />
                </table>
            </div>

            <div class="flex flex-row-reverse">
                <Pagination class="mt-6" :links="links" />
            </div>
        </div>
    </div>
</template>
