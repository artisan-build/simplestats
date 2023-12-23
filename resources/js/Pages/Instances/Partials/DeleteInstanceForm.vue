<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    project: Object,
});

const confirmingProjectDeletion = ref(false);
const form = useForm({});

const confirmProjectDeletion = () => {
    confirmingProjectDeletion.value = true;
};

const deleteProject = () => {
    form.delete(route('projects.destroy', props.project), {
        errorBag: 'deleteProject',
    });
};
</script>

<template>
    <ActionSection>
        <template #title>
            Delete Project
        </template>

        <template #description>
            Permanently delete this project.
        </template>

        <template #content>
            <div class="max-w-xl text-sm text-gray-600 dark:text-gray-400">
                Once a project is deleted, all of its instances and data will be permanently deleted.
            </div>

            <div class="mt-5">
                <DangerButton @click="confirmProjectDeletion">
                    Delete Project
                </DangerButton>
            </div>

            <!-- Delete Project Confirmation Modal -->
            <ConfirmationModal :show="confirmingProjectDeletion" @close="confirmingProjectDeletion = false">
                <template #title>
                    Delete Project
                </template>

                <template #content>
                    Are you sure you want to delete this project? Once a project is deleted, all of its instances and data will be permanently deleted.
                </template>

                <template #footer>
                    <SecondaryButton @click="confirmingProjectDeletion = false">
                        Cancel
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteProject"
                    >
                        Delete Project
                    </DangerButton>
                </template>
            </ConfirmationModal>
        </template>
    </ActionSection>
</template>
