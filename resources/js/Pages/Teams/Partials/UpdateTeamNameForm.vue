<script setup>
import {useForm} from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import {onMounted, ref} from "vue";
import SelectInput from "@/Components/SelectInput.vue";

const props = defineProps({
    team: Object,
    permissions: Object,
});

const timezones = ref([]);

const form = useForm({
    name: props.team.name,
    currency: props.team.currency,
    timezone: props.team.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
});

const fetchTimezones = async () => {
    const response = await axios.get(route('timezones.index'));
    timezones.value = response.data;
};

onMounted(() => {
    fetchTimezones();
});

const updateTeamName = () => {
    form.put(route('teams.update', props.team), {
        errorBag: 'updateTeam',
        preserveScroll: true,
    });
};
</script>

<template>
    <FormSection @submitted="updateTeamName">
        <template #title>
            Team Details
        </template>

        <template #description>
            The team details and information.
        </template>

        <template #form>
            <!-- Team Owner Information -->
            <div class="col-span-6">
                <InputLabel value="Owner" />

                <div class="flex items-center mt-2">
                    <img v-if="$page.props.jetstream.managesProfilePhotos" class="mr-4 w-12 h-12 rounded-full object-cover" :src="team.owner.profile_photo_url" :alt="team.owner.name">

                    <div class="leading-tight">
                        <span class="text-gray-900 dark:text-white">{{ team.owner.name }}</span>
                        <span class="text-gray-700 dark:text-dark-300 text-sm">
                            ({{ team.owner.email }})
                        </span>
                    </div>
                </div>
            </div>

            <!-- Team Name -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="! permissions.canUpdateTeam"
                />

                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <!-- Team Currency -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="currency" value="Currency" />

                <TextInput
                    id="currency"
                    v-model="form.currency"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="! permissions.canUpdateTeam"
                    placeholder="USD"
                />

                <InputError :message="form.errors.currency" class="mt-2" />
            </div>

            <!-- Team Timezone -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="timezone" value="Timezone" />

                <SelectInput
                    id="timezone"
                    v-model="form.timezone"
                    class="mt-1 block w-full"
                    :disabled="! permissions.canUpdateTeam"
                    :options="timezones"
                />

                <InputError :message="form.errors.timezone" class="mt-2" />
            </div>
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
