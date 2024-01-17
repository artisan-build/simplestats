<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    instance: Object,
});

const confirmingInstanceDeletion = ref(false);
const form = useForm({});

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
            <div class="max-w-xl text-sm text-gray-600 dark:text-gray-400">
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
