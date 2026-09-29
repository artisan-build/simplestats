<script setup>
import {usePage} from '@inertiajs/vue3';
import {usePasskeyVerify} from '@laravel/passkeys/vue';
import InputError from '@/Components/InputError.vue';
import FingerprintIcon from 'bootstrap-icons/icons/fingerprint.svg?component';

const emit = defineEmits(['confirmed']);

const page = usePage();

const {verify, isLoading, error, isSupported} = usePasskeyVerify({
    routes: {
        options: route('passkey.confirm-options'),
        submit: route('passkey.confirm'),
    },
    onSuccess: () => emit('confirmed'),
});
</script>

<template>
    <div v-if="isSupported && page.props.auth.user?.has_passkeys" class="mt-4">
        <button type="button"
                class="flex gap-2 items-center justify-center w-full py-2.5 border border-gray-400 dark:border-dark-600 rounded-lg text-sm font-medium text-gray-700 dark:text-dark-300 shadow-sm hover:shadow-md dark:hover:border-dark-400 transition duration-200 disabled:opacity-25"
                :disabled="isLoading"
                @click="verify">
            <FingerprintIcon class="size-5" />
            Confirm with passkey
        </button>

        <InputError :message="error" class="mt-2" />

        <div class="flex items-center pt-5">
            <div class="flex-grow border-t border-gray-200 dark:border-dark-500"></div>
            <span class="flex-shrink px-4 text-xs text-gray-400 dark:text-dark-500">or enter your password</span>
            <div class="flex-grow border-t border-gray-200 dark:border-dark-500"></div>
        </div>
    </div>
</template>
