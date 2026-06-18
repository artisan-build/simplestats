<script setup>
defineProps({
    // [{value, label}]
    options: Array,
    modelValue: [String, Number, Boolean],
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div class="inline-flex rounded-md border border-gray-200 dark:border-dark-600 p-0.5 text-xs font-medium">
        <button v-for="option in options" :key="String(option.value)"
                type="button"
                class="px-2.5 py-1 rounded"
                :class="modelValue === option.value ? 'bg-indigo-100 dark:bg-dark-700 text-indigo-600' : 'text-gray-500 dark:text-dark-300 hover:text-indigo-500'"
                @click="emit('update:modelValue', option.value)"
        >
            {{ option.label }}
        </button>
    </div>
</template>
