<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { usePasskeyVerify } from '@laravel/passkeys/vue';
import FingerprintIcon from 'bootstrap-icons/icons/fingerprint.svg?component';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const form = useForm({
    password: '',
});

const passwordInput = ref(null);

const {
    verify: confirmWithPasskey,
    isLoading: passkeyLoading,
    error: passkeyError,
    isSupported: passkeySupported,
} = usePasskeyVerify({
    routes: {
        options: route('passkey.confirm-options'),
        submit: route('passkey.confirm'),
    },
    onSuccess: (response) => router.visit(response.redirect),
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();

            passwordInput.value.focus();
        },
    });
};
</script>

<template>
    <Head :title="$t('Secure Area')" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <template #title>
            {{ $t('Password Confirmation') }}
        </template>

        <div class="mb-4 text-sm text-gray-600 dark:text-dark-400">
            {{ $t('This is a secure area of the application. Please confirm your password before continuing.') }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <TextInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                    autofocus
                    :placeholder="$t('Password')"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <PrimaryButton class="w-full my-6" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    <div class="w-full py-1 text-base normal-case">
                        {{ $t('Confirm') }}
                    </div>
                </PrimaryButton>

                <template v-if="passkeySupported && $page.props.auth.user?.has_passkeys">
                    <button type="button"
                            class="flex gap-2 items-center justify-center transition duration-200 border border-gray-400 dark:border-dark-600 w-full py-2.5 rounded-lg text-sm shadow-sm hover:shadow-md dark:hover:border-dark-400 font-medium text-gray-700 dark:text-dark-300"
                            :class="{ 'opacity-25': passkeyLoading }"
                            :disabled="passkeyLoading"
                            @click="confirmWithPasskey">
                        <FingerprintIcon class="size-5" />
                        {{ $t('Confirm with passkey') }}
                    </button>
                    <InputError class="mt-2 text-center" :message="passkeyError" />
                </template>
            </div>
        </form>
    </AuthenticationCard>
</template>
