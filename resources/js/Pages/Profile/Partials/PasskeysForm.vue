<script setup>
import {ref} from 'vue';
import {router} from '@inertiajs/vue3';
import {usePasskeyRegister} from '@laravel/passkeys/vue';
import ActionSection from '@/Components/ActionSection.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import ConfirmsPassword from '@/Components/ConfirmsPassword.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import FingerprintIcon from 'bootstrap-icons/icons/fingerprint.svg?component';

defineProps({
    passkeys: Array,
});

const name = ref('');
const passkeyBeingDeleted = ref(null);
const deleting = ref(false);
const deleteError = ref(null);

const reloadPasskeys = () => router.reload({only: ['passkeys', 'auth']});

const {register, isLoading: registering, error: registerError, isSupported} = usePasskeyRegister({
    onSuccess: () => {
        name.value = '';
        reloadPasskeys();
    },
});

const addPasskey = () => register(name.value);

const deletePasskey = () => {
    deleting.value = true;
    deleteError.value = null;

    axios.delete(route('passkey.destroy', passkeyBeingDeleted.value.uuid))
        .then(() => {
            passkeyBeingDeleted.value = null;
            reloadPasskeys();
        })
        .catch(() => deleteError.value = 'The passkey could not be removed. Please try again.')
        .finally(() => deleting.value = false);
};

const formatDate = (date) => date ? new Date(date).toLocaleDateString() : 'Never';
</script>

<template>
    <ActionSection>
        <template #title>
            Passkeys
        </template>

        <template #description>
            Sign in with your fingerprint, face or device PIN instead of your password.
        </template>

        <template #content>
            <div v-if="passkeys.length" class="space-y-4">
                <div v-for="passkey in passkeys" :key="passkey.uuid" class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <FingerprintIcon class="size-6 text-gray-500 dark:text-dark-400" />

                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-dark-100">
                                {{ passkey.name }}
                                <span v-if="passkey.authenticator" class="font-normal text-gray-500 dark:text-dark-400">({{ passkey.authenticator }})</span>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-dark-400">
                                Added {{ formatDate(passkey.created_at) }}, last used {{ formatDate(passkey.last_used_at) }}
                            </div>
                        </div>
                    </div>

                    <ConfirmsPassword @confirmed="passkeyBeingDeleted = passkey">
                        <DangerButton>
                            Remove
                        </DangerButton>
                    </ConfirmsPassword>
                </div>
            </div>

            <div v-else class="text-sm text-gray-600 dark:text-dark-400">
                You have not added any passkeys yet.
            </div>

            <div v-if="isSupported" class="mt-6 flex flex-col sm:flex-row sm:items-start gap-3">
                <div class="grow">
                    <TextInput
                        v-model="name"
                        type="text"
                        class="block w-full"
                        placeholder="Passkey name, e.g. MacBook"
                        maxlength="255"
                    />
                    <InputError :message="registerError" class="mt-2" />
                </div>

                <ConfirmsPassword @confirmed="addPasskey">
                    <PrimaryButton :class="{ 'opacity-25': registering }" :disabled="registering || !name">
                        Add passkey
                    </PrimaryButton>
                </ConfirmsPassword>
            </div>

            <div v-else class="mt-6 text-sm text-gray-600 dark:text-dark-400">
                This browser does not support passkeys.
            </div>

            <ConfirmationModal :show="passkeyBeingDeleted !== null" @close="passkeyBeingDeleted = null">
                <template #title>
                    Remove passkey
                </template>

                <template #content>
                    Are you sure you want to remove the passkey "{{ passkeyBeingDeleted?.name }}"? You can no longer sign in with it.
                    <InputError :message="deleteError" class="mt-2" />
                </template>

                <template #footer>
                    <SecondaryButton @click="passkeyBeingDeleted = null">
                        Cancel
                    </SecondaryButton>

                    <DangerButton class="ms-3" :class="{ 'opacity-25': deleting }" :disabled="deleting" @click="deletePasskey">
                        Remove passkey
                    </DangerButton>
                </template>
            </ConfirmationModal>
        </template>
    </ActionSection>
</template>
