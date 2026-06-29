<script setup>
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import UsageSelector from "@/Components/Project/UsageSelector.vue";

defineProps({
    modelValue: Number,
    usages: Object,
    error: String,
    stacked: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div :class="stacked ? 'mb-6' : 'col-span-6'">
        <InputLabel value="What would you like to track?" />
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Select all options that apply to your use case.
        </p>
        <div class="mt-2">
            <UsageSelector
                :modelValue="modelValue"
                :usages="usages"
                :stacked="stacked"
                @update:modelValue="$emit('update:modelValue', $event)"
            />
        </div>
        <InputError :message="error" class="mt-2" />
    </div>
</template>
