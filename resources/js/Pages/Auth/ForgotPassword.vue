<script setup>
import {Head, Link, useForm} from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import {useLocale} from '@/Composables/useLocale.js';

const { localizedPath } = useLocale();

defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head :title="$t('Forgot Password')" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <template #title>
            {{ $t('Reset your password') }}
        </template>

        <div class="mb-4 text-sm text-gray-600 dark:text-dark-400">
            {{ $t('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
        </div>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="username"
                    :placeholder="$t('Email')"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <PrimaryButton class="w-full my-6" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    <div class="w-full py-1 text-base normal-case">
                        {{ $t('Email password reset link') }}
                    </div>
                </PrimaryButton>
            </div>

            <div class="mt-4 text-center">
                <Link :href="localizedPath('/login')" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-100">
                    <span class="text-gray-600">{{ $t('Back to sign in') }}</span>
                </Link>
            </div>
        </form>
    </AuthenticationCard>
</template>
