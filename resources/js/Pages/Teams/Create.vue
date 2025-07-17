<script setup>
import {useForm, usePage} from "@inertiajs/vue3";
import {onMounted, ref} from "vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import SelectInput from "@/Components/SelectInput.vue";
import TrackingUsageSelector from "@/Components/TrackingUsageSelector.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import FullscreenCard from "@/Components/FullscreenCard.vue";

const page = usePage();

const props = defineProps({
    team: Object,
});

const timezones = ref([]);

const form = useForm({
    name: props.team?.name || page.props.auth.user.current_team?.name || '',
    currency: props.team?.currency || page.props.auth.user.current_team?.currency || '',
    timezone: props.team?.timezone || page.props.auth.user.current_team?.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
});

const fetchTimezones = async () => {
    const response = await axios.get(route('timezones.index'));
    timezones.value = response.data;
};

onMounted(() => {
    fetchTimezones();
});

const submit = () => {
    if (props.team) {
        form.put(route('teams.update', props.team), {
            errorBag: 'updateTeam',
        });
    } else {
        form.post(route('teams.store'), {
            errorBag: 'createTeam',
        });
    }
};
</script>

<template>
    <FullscreenCard title="Create Team" description="Please provide your team and project details.">
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

            <div class="mb-6">
                <label for="project" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Name of your project
                </label>
                <TextInput
                    id="project"
                    v-model="form.project"
                    type="text"
                    class="block w-full dark:bg-dark-950"
                    placeholder="My great SaaS"
                />
                <InputError :message="form.errors.project" class="mt-2" />
            </div>

            <div class="hidden mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    What do you want to track?
                </label>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                    Select all options that fit your needs!
                </p>
                <div class="w-full">
                    <TrackingUsageSelector :trackingUsages="trackingUsages" v-model="form.usage" border="4" />
                    <div class="text-sm mt-2 text-gray-500 dark:text-gray-400">
                        In most cases, it's best to select all options…
                    </div>
                </div>
                <InputError :message="form.errors.usage" class="mt-2" />
            </div>

            <div class="mt-8">
                <PrimaryButton class="w-full !text-sm justify-center !p-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Create Team
                </PrimaryButton>
            </div>
        </form>
    </FullscreenCard>
</template>
