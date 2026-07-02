<script setup>
import {computed} from 'vue';
import {router} from '@inertiajs/vue3';
import CaretUpIcon from 'bootstrap-icons/icons/caret-up-fill.svg?component';
import CaretDownIcon from 'bootstrap-icons/icons/caret-down-fill.svg?component';

const props = defineProps({
    // value sent to the backend as ?sort=. The backend decides whether it supports it.
    sortKey: {type: String, required: true},
    // currently active sort key + direction, echoed back from the page props.
    active: {type: String, default: null},
    direction: {type: String, default: 'desc'},
    routeName: {type: String, required: true},
    // extra query params to keep across the navigation (project_id, range, ...).
    params: {type: Object, default: () => ({})},
});

const isActive = computed(() => props.active === props.sortKey);

// Cycle desc -> asc -> default (unsorted): the third click drops the sort params
// so the backend falls back to its default ordering.
const sort = () => {
    const query = {...props.params};
    if (!isActive.value) {
        query.sort = props.sortKey;
        query.direction = 'desc';
    } else if (props.direction === 'desc') {
        query.sort = props.sortKey;
        query.direction = 'asc';
    }
    router.get(route(props.routeName), query, {preserveScroll: true, preserveState: false});
};
</script>

<template>
    <th>
        <button
            type="button"
            class="group inline-flex items-center gap-1 font-bold"
            @click="sort"
        >
            <span class="group-hover:underline"><slot /></span>
            <CaretUpIcon v-if="isActive && direction === 'asc'" class="size-3 text-gray-700 dark:text-dark-200" />
            <CaretDownIcon v-else-if="isActive" class="size-3 text-gray-700 dark:text-dark-200" />
            <CaretDownIcon v-else class="size-3 text-gray-300 transition group-hover:text-gray-500 dark:text-dark-600 dark:group-hover:text-dark-400" />
        </button>
    </th>
</template>
