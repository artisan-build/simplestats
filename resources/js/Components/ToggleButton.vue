
<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        default: false,
    },
    value: {
        type: String,
        default: null,
    },
    buttonText: {
        type: String,
        required: true
    }
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },
    set(val) {
        emit('update:checked', val);
    }
});

const toggleChecked = () => {
    proxyChecked.value = !proxyChecked.value;
};
</script>

<template>
    <button
        class="inline-flex items-center px-4 py-3  border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150"
        :class="proxyChecked ? 'bg-indigo-100' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700'"
        @click="toggleChecked"
    >
        {{ buttonText }}
    </button>
</template>
