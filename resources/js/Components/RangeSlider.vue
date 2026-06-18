<script setup>
defineProps({
    modelValue: {
        type: Number,
        required: true,
    },
    min: {
        type: Number,
        default: 0,
    },
    max: {
        type: Number,
        required: true,
    },
    step: {
        type: Number,
        default: 1,
    },
    minLabel: {
        type: String,
        default: '',
    },
    maxLabel: {
        type: String,
        default: '',
    },
    ariaLabel: {
        type: String,
        default: '',
    },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div>
        <input
            type="range"
            :min="min"
            :max="max"
            :step="step"
            :value="modelValue"
            :aria-label="ariaLabel"
            class="w-full accent-indigo-500 cursor-pointer"
            @input="$emit('update:modelValue', Number($event.target.value))"
        />
        <div v-if="minLabel || maxLabel || $slots.min || $slots.max" class="flex justify-between mt-2 text-sm text-gray-500 dark:text-dark-400">
            <span><slot name="min">{{ minLabel }}</slot></span>
            <span><slot name="max">{{ maxLabel }}</slot></span>
        </div>
    </div>
</template>
