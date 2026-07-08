<script setup>
import {computed} from 'vue';
import {useForm} from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';

const props = defineProps({
    team: Object,
    aiSettings: Object,
    permissions: Object,
});

const providerLabels = {
    anthropic: 'Anthropic (Claude)',
    gemini: 'Google (Gemini)',
    openai: 'OpenAI (GPT)',
    groq: 'Groq',
    deepseek: 'DeepSeek',
    mistral: 'Mistral',
    xai: 'xAI (Grok)',
    openrouter: 'OpenRouter',
};

const providerOptions = [
    {id: '', name: 'SimpleStats default'},
    ...props.aiSettings.availableProviders.map((provider) => ({
        id: provider,
        name: providerLabels[provider] ?? provider,
    })),
];

const form = useForm({
    ai_provider: props.aiSettings.provider ?? '',
    ai_model: props.aiSettings.model ?? '',
    ai_api_key: '',
});

const usesOwnProvider = computed(() => form.ai_provider !== '');

const updateAiSettings = () => {
    form.transform((data) => ({
        ...data,
        ai_provider: data.ai_provider || null,
        ai_model: data.ai_model || null,
        ai_api_key: data.ai_api_key || null,
    })).put(route('teams.ai-settings.update', props.team), {
        preserveScroll: true,
        onSuccess: () => form.reset('ai_api_key'),
    });
};
</script>

<template>
    <FormSection @submitted="updateAiSettings">
        <template #title>
            AI Assistant
        </template>

        <template #description>
            Optionally use your own AI provider and API key for the Ask AI assistant. Your key is stored encrypted, is only used for your team's chats, and is never shown again after saving.
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="ai_provider" value="Provider" />

                <SelectInput
                    id="ai_provider"
                    v-model="form.ai_provider"
                    class="mt-1 block w-full"
                    :disabled="! permissions.canUpdateTeam"
                    :options="providerOptions"
                />

                <InputError :message="form.errors.ai_provider" class="mt-2" />
            </div>

            <template v-if="usesOwnProvider">
                <div class="col-span-6 sm:col-span-4">
                    <InputLabel for="ai_api_key" value="API Key" />

                    <TextInput
                        id="ai_api_key"
                        v-model="form.ai_api_key"
                        type="password"
                        autocomplete="off"
                        class="mt-1 block w-full"
                        :disabled="! permissions.canUpdateTeam"
                        :placeholder="aiSettings.keySet ? '•••••••• (key is set, enter to replace)' : ''"
                    />

                    <InputError :message="form.errors.ai_api_key" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-4">
                    <InputLabel for="ai_model" value="Model" />

                    <TextInput
                        id="ai_model"
                        v-model="form.ai_model"
                        type="text"
                        class="mt-1 block w-full"
                        :disabled="! permissions.canUpdateTeam"
                        placeholder="Provider default"
                    />

                    <InputError :message="form.errors.ai_model" class="mt-2" />
                </div>
            </template>
        </template>

        <template v-if="permissions.canUpdateTeam" #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Saved.
            </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Save
            </PrimaryButton>
        </template>
    </FormSection>
</template>
