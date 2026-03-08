<script setup>
import {ref} from 'vue';
import {router, useForm} from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    show: Boolean,
    allPresets: Array,
    hiddenPresets: Array,
    customPresets: Array,
    rangeTypes: Array,
});

const emit = defineEmits(['close']);

const rangeTypeOptions = [
    {id: 'days', name: 'Days'},
    {id: 'weeks', name: 'Weeks'},
    {id: 'months', name: 'Months'},
    {id: 'years', name: 'Years'},
];

const rangeAmounts = Array.from({length: 60}, (_, i) => ({
    id: (i + 1).toString(),
    name: (i + 1).toString(),
}));

const form = useForm({
    name: '',
    range_amount: '7',
    range_type: 'days',
});

const editingPreset = ref(null);

const startEditing = (preset) => {
    editingPreset.value = preset.id;
    form.name = preset.name;
    form.range_amount = preset.range_amount.toString();
    form.range_type = preset.range_type;
};

const cancelEditing = () => {
    editingPreset.value = null;
    form.reset();
    form.clearErrors();
};

const savePreset = () => {
    if (editingPreset.value) {
        form.put(route('custom-presets.update', editingPreset.value), {
            preserveScroll: true,
            onSuccess: () => {
                cancelEditing();
                router.reload();
            },
        });
    } else {
        form.post(route('custom-presets.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                form.clearErrors();
                router.reload();
            },
        });
    }
};

const deletePreset = (presetId) => {
    if (confirm('Are you sure you want to delete this preset?')) {
        router.delete(route('custom-presets.destroy', presetId), {
            preserveScroll: true,
            onSuccess: () => {
                router.reload();
            },
        });
    }
};

const toggleBuiltin = (presetId) => {
    router.post(route('custom-presets.toggle-builtin'), {
        preset: presetId,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            router.reload();
        },
    });
};

const isHidden = (presetId) => {
    return props.hiddenPresets.includes(presetId);
};
</script>

<template>
    <DialogModal :show="show" @close="emit('close')" max-width="lg">
        <template #title>Manage Presets</template>

        <template #content>
            <div class="space-y-6">
                <!-- Built-in Presets -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Built-in Presets</h3>
                    <div class="space-y-1">
                        <div
                            v-for="preset in allPresets"
                            :key="preset.id"
                            class="flex items-center justify-between py-1.5 px-2 rounded hover:bg-gray-50 dark:hover:bg-dark-700"
                        >
                            <span class="text-sm" :class="{'text-gray-400 dark:text-gray-500 line-through': isHidden(preset.id)}">
                                {{ preset.name }}
                            </span>
                            <button
                                type="button"
                                class="text-xs px-2 py-1 rounded"
                                :class="isHidden(preset.id) ? 'text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20' : 'text-gray-400 hover:bg-gray-100 dark:hover:bg-dark-600'"
                                @click="toggleBuiltin(preset.id)"
                            >
                                {{ isHidden(preset.id) ? 'Show' : 'Hide' }}
                            </button>
                        </div>
                    </div>
                </div>

                <hr class="dark:border-dark-600">

                <!-- Custom Presets -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        Custom Presets
                        <span class="text-xs font-normal text-gray-400">({{ customPresets.length }}/10)</span>
                    </h3>

                    <!-- Existing Custom Presets -->
                    <div v-if="customPresets.length > 0" class="space-y-1 mb-4">
                        <div
                            v-for="preset in customPresets"
                            :key="preset.id"
                            class="flex items-center justify-between py-1.5 px-2 rounded hover:bg-gray-50 dark:hover:bg-dark-700"
                        >
                            <span class="text-sm">
                                {{ preset.name }}
                                <span class="text-xs text-gray-400">({{ preset.range_amount }} {{ preset.range_type }})</span>
                            </span>
                            <div class="flex gap-1">
                                <button
                                    type="button"
                                    class="text-xs px-2 py-1 rounded text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20"
                                    @click="startEditing(preset)"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="text-xs px-2 py-1 rounded text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                                    @click="deletePreset(preset.id)"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Create/Edit Form -->
                    <div v-if="customPresets.length < 10 || editingPreset" class="bg-gray-50 dark:bg-dark-700 rounded-lg p-3">
                        <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">
                            {{ editingPreset ? 'Edit Preset' : 'New Preset' }}
                        </h4>
                        <form @submit.prevent="savePreset" class="space-y-3">
                            <div>
                                <InputLabel for="presetName" value="Name" class="text-xs" />
                                <TextInput
                                    id="presetName"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full text-sm"
                                    placeholder="e.g. Last 14 Days"
                                    maxlength="50"
                                />
                                <InputError :message="form.errors.name" class="mt-1" />
                            </div>
                            <div class="flex gap-2">
                                <div class="flex-1">
                                    <InputLabel for="presetAmount" value="Amount" class="text-xs" />
                                    <SelectInput
                                        id="presetAmount"
                                        v-model="form.range_amount"
                                        :options="rangeAmounts"
                                        class="mt-1 block w-full text-sm"
                                    />
                                    <InputError :message="form.errors.range_amount" class="mt-1" />
                                </div>
                                <div class="flex-1">
                                    <InputLabel for="presetType" value="Type" class="text-xs" />
                                    <SelectInput
                                        id="presetType"
                                        v-model="form.range_type"
                                        :options="rangeTypeOptions"
                                        class="mt-1 block w-full text-sm"
                                    />
                                    <InputError :message="form.errors.range_type" class="mt-1" />
                                </div>
                            </div>
                            <div class="flex gap-2 justify-end">
                                <SecondaryButton v-if="editingPreset" type="button" @click="cancelEditing" class="text-xs">
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton type="submit" :disabled="form.processing" class="text-xs">
                                    {{ editingPreset ? 'Update' : 'Create' }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <SecondaryButton @click="emit('close')">
                Close
            </SecondaryButton>
        </template>
    </DialogModal>
</template>
