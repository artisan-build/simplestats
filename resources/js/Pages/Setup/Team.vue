<script setup>
import {useForm} from "@inertiajs/vue3";
import {onMounted, ref} from "vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import SelectInput from "@/Components/SelectInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import FullscreenCard from "@/Components/FullscreenCard.vue";

const props = defineProps({
    team: Object,
});

const timezones = ref([]);

const form = useForm({
    name: props.team.name || '',
    currency: props.team.currency || '',
    timezone: props.team.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
});

const fetchTimezones = async () => {
    const response = await axios.get(route('timezones.index'));
    timezones.value = response.data;
};

onMounted(() => {
    fetchTimezones();
});

const submit = () => {
    form.post(route('setup.team.store'), {
        errorBag: 'updateTeam',
    });
};

const action = (props.team.currency) ? 'Update Team' : 'Create Team';
</script>

<template>
    <FullscreenCard :title="action" description="Provide your team details.">
        <form @submit.prevent="submit">
            <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Name
                </label>
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="block w-full dark:bg-dark-950"
                />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <div class="mb-6">
                <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Currency
                </label>
                <p class="text-sm text-gray-600 dark:text-dark-400 mb-2">
                    All multi-currency payments are automatically converted to your selected team currency using daily exchange rates.
                </p>
                <TextInput
                    id="currency"
                    v-model="form.currency"
                    type="text"
                    class="block w-full dark:bg-dark-950"
                    placeholder="USD"
                />
                <InputError :message="form.errors.currency" class="mt-2" />
            </div>

            <div class="mb-6">
                <label for="timezone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Timezone
                </label>
                <SelectInput
                    id="timezone"
                    v-model="form.timezone"
                    class="block w-full dark:bg-dark-950"
                    :options="timezones"
                />
                <InputError :message="form.errors.timezone" class="mt-2" />
            </div>

            <div class="mt-8">
                <PrimaryButton class="w-full !text-sm justify-center !p-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    {{ action }}
                </PrimaryButton>
            </div>
        </form>
    </FullscreenCard>
</template>
