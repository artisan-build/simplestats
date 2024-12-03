<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Socialstream from '@/Components/Socialstream.vue';
import TextInput from '@/Components/TextInput.vue';
import {onMounted} from "vue";

const props = defineProps({
    params: Object,
});

const form = useForm({
    name: '',
    email: props.params.invitationMail ?? '',
    password: '',
    password_confirmation: '',
    terms: true,
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const appName = import.meta.env.VITE_APP_NAME;
</script>

<template>
    <Head title="Sign up" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <template #title>
            Sign up to {{ appName }}<br>
            <span class="text-sm text-gray-500" v-if="params.teamName">and join the following team: {{ params.teamName }}</span>
        </template>

        <Socialstream v-if="$page.props.appType === 'default' && $page.props.socialstream.show && $page.props.socialstream.providers.length"
                      :error="$page.props?.errors?.socialstream || null" :prompt="$page.props.socialstream.prompt"
                      :labels="$page.props.socialstream.labels" :providers="$page.props.socialstream.providers"/>

        <form @submit.prevent="submit">
            <div>
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Name"
                />
                <InputError class="mt-3 -mb-1 text-left" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autocomplete="username"
                    placeholder="Email"
                />
                <InputError class="mt-3 -mb-1 text-left" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="new-password"
                    placeholder="Password"
                />
                <InputError class="mt-3 -mb-1 text-left" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="new-password"
                    placeholder="Confirm Password"
                />
                <InputError class="mt-3 -mb-1 text-left" :message="form.errors.password_confirmation" />
            </div>

            <div class="mt-4">
                <PrimaryButton class="w-full my-6" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    <div class="w-full py-1 text-base normal-case">
                        Create account
                    </div>
                </PrimaryButton>
            </div>

            <div v-if="$page.props.appType === 'default' && $page.props.jetstream.hasTermsAndPrivacyPolicyFeature" class="mt-4 text-center">
                <InputLabel for="terms">
                    <div class="flex items-center px-2 lg:px-6">
                        <Checkbox class="hidden" id="terms" v-model:checked="form.terms" name="terms" required />

                        <div>
                            Signing up for a {{ appName }} account means you agree to the <a target="_blank" :href="route('terms.show')" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-100">Terms of Service</a> and <a target="_blank" :href="route('policy.show')" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-100">Privacy Policy</a>.
                        </div>
                    </div>
                    <InputError class="mt-3 -mb-1 text-left" :message="form.errors.terms" />
                </InputLabel>
            </div>

            <div class="mt-4 text-center">
                <Link :href="route('login')" class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-100">
                    <span class="text-gray-600">Already have an account?</span> Sign in
                </Link>
            </div>

            <InputError class="mt-3 -mb-1 text-left" :message="form.errors.accept_invitation_link" />
        </form>
    </AuthenticationCard>
</template>
