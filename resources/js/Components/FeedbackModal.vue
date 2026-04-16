<script setup>
import {useForm, usePage} from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const typeOptions = usePage().props.feedbackTypes;

const form = useForm({
    type: 'feedback',
    message: '',
});

const submit = () => {
    form.post(route('feedback.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.clearErrors();
            emit('close');
        },
    });
};

const close = () => {
    form.clearErrors();
    emit('close');
};
</script>

<template>
    <DialogModal :show="show" @close="close" max-width="lg">
        <template #title>
            Send us Feedback
        </template>

        <template #content>
            <p class="text-sm text-gray-500 dark:text-dark-400 mb-4">
                We read every message. Whether it's a new idea, a suggestion, or a bug report, we'd love to hear from you.
            </p>
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <InputLabel for="type" value="Type" />
                    <SelectInput
                        id="type"
                        v-model="form.type"
                        :options="typeOptions"
                        class="mt-1 w-full"
                    />
                    <InputError :message="form.errors.type" class="mt-1" />
                </div>

                <div>
                    <InputLabel for="message" value="Message" />
                    <textarea
                        id="message"
                        v-model="form.message"
                        rows="5"
                        class="mt-1 w-full border-gray-300 dark:border-dark-700 dark:bg-dark-900 dark:text-dark-300 dark:placeholder-gray-400 focus:border-indigo-500 dark:focus:border-indigo-500 focus:ring-indigo-500 dark:focus:ring-indigo-500 rounded-md shadow-sm"
                        placeholder="Tell us what you think..."
                    />
                    <InputError :message="form.errors.message" class="mt-1" />
                </div>
            </form>
        </template>

        <template #footer>
            <SecondaryButton @click="close" class="mr-3">
                Cancel
            </SecondaryButton>
            <PrimaryButton @click="submit" :disabled="form.processing">
                Send
            </PrimaryButton>
        </template>
    </DialogModal>
</template>
