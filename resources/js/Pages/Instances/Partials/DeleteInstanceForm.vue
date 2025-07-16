<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    instance: Object,
});

const confirmingInstanceDeletion = ref(false);
const form = useForm({
    password: '',
});

const confirmInstanceDeletion = () => {
    confirmingInstanceDeletion.value = true;
};

const deleteInstance = () => {
    form.delete(route('projects.instances.destroy', [props.instance.project_id, props.instance.id]), {
        errorBag: 'deleteInstance',
    });
};
</script>

<template>
    <ActionSection>
        <template #title>
            Delete Instance
        </template>

        <template #description>
            Permanently delete this instance.
        </template>

        <template #content>
            <div class="max-w-xl text-sm text-gray-600 dark:text-dark-400">
                Once an Instance is deleted, all of its data will be permanently deleted.
            </div>

            <div class="mt-5">
                <DangerButton @click="confirmInstanceDeletion">
                    Delete Instance
                </DangerButton>
            </div>

            <!-- Delete Instance Confirmation Modal -->
            <ConfirmationModal :show="confirmingInstanceDeletion" @close="confirmingInstanceDeletion = false">
                <template #title>
                    Delete Instance
                </template>

                <template #content>
                    Are you sure you want to delete this instance? Once an Instance is deleted, all of its data will be permanently deleted.
                    Please enter your password to confirm you would like to permanently delete this instance.

                    <div class="mt-4">
                        <TextInput
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-3/4"
                            placeholder="Password"
                            autocomplete="current-password"
                            @keyup.enter="deleteInstance"
                        />

                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>
                </template>

                <template #footer>
                    <SecondaryButton @click="confirmingInstanceDeletion = false">
                        Cancel
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteInstance"
                    >
                        Delete Instance
                    </DangerButton>
                </template>
            </ConfirmationModal>
        </template>
    </ActionSection>
</template>
