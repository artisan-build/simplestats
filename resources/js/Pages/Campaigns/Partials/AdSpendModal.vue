<script setup>
import {watch} from 'vue';
import {router, useForm} from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    show: Boolean,
    editSpend: {type: Object, default: null},
    availableCampaigns: Array,
    projects: Array,
    defaultProjectId: [Number, String],
    teamCurrency: String,
});

const emit = defineEmits(['close']);

const form = useForm({
    project_id: props.defaultProjectId,
    stats_track_campaign_id: props.availableCampaigns[0]?.id,
    amount: '',
    currency: props.teamCurrency,
    starts_on: '',
    ends_on: '',
});

const initForm = () => {
    if (props.editSpend) {
        form.project_id = props.editSpend.project_id;
        form.stats_track_campaign_id = props.editSpend.stats_track_campaign_id;
        form.amount = (props.editSpend.amount / 100).toString();
        form.currency = props.editSpend.currency;
        form.starts_on = props.editSpend.starts_on;
        form.ends_on = props.editSpend.ends_on;
    } else {
        form.project_id = props.defaultProjectId;
        form.stats_track_campaign_id = props.availableCampaigns[0]?.id;
        form.amount = '';
        form.currency = props.teamCurrency;
        form.starts_on = '';
        form.ends_on = '';
    }

    form.clearErrors();
};

watch(() => props.show, (open) => {
    if (open) {
        initForm();
    }
});

const save = () => {
    const onSuccess = () => {
        emit('close');
        router.reload({only: ['campaigns', 'adSpends']});
    };

    if (props.editSpend) {
        form.put(route('ad-spends.update', props.editSpend.id), {preserveScroll: true, onSuccess});
    } else {
        form.post(route('ad-spends.store'), {preserveScroll: true, onSuccess});
    }
};
</script>

<template>
    <DialogModal :show="show" @close="emit('close')" max-width="2xl">
        <template #title>{{ editSpend ? 'Edit ad spend' : 'Add ad spend' }}</template>

        <template #content>
            <div v-if="!availableCampaigns.length" class="text-sm text-gray-500 dark:text-dark-400">
                No campaigns tracked yet. Ad spend can be assigned once visitors arrive through a UTM campaign.
            </div>

            <form v-else @submit.prevent="save" class="space-y-4">
                <div class="flex gap-3">
                    <div class="flex-1">
                        <InputLabel for="campaign" value="Campaign" />
                        <SelectInput id="campaign" v-model="form.stats_track_campaign_id" :options="availableCampaigns" class="mt-1 block w-full" />
                        <InputError :message="form.errors.stats_track_campaign_id" class="mt-1" />
                    </div>
                    <div v-if="projects.length > 1" class="flex-1">
                        <InputLabel for="project" value="Project" />
                        <SelectInput id="project" v-model="form.project_id" :options="projects" class="mt-1 block w-full" />
                        <InputError :message="form.errors.project_id" class="mt-1" />
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="flex-1">
                        <InputLabel for="amount" :value="`Amount (${form.currency})`" />
                        <TextInput id="amount" v-model="form.amount" type="number" step="0.01" min="0" class="mt-1 block w-full" placeholder="e.g. 500" />
                        <InputError :message="form.errors.amount" class="mt-1" />
                    </div>
                    <div class="flex-1">
                        <InputLabel for="starts_on" value="From" />
                        <TextInput id="starts_on" v-model="form.starts_on" type="date" class="mt-1 block w-full" />
                        <InputError :message="form.errors.starts_on" class="mt-1" />
                    </div>
                    <div class="flex-1">
                        <InputLabel for="ends_on" value="To" />
                        <TextInput id="ends_on" v-model="form.ends_on" type="date" class="mt-1 block w-full" />
                        <InputError :message="form.errors.ends_on" class="mt-1" />
                    </div>
                </div>
            </form>
        </template>

        <template #footer>
            <SecondaryButton @click="emit('close')">Cancel</SecondaryButton>
            <PrimaryButton v-if="availableCampaigns.length" class="ms-3" :disabled="form.processing" @click="save">
                {{ editSpend ? 'Update' : 'Add' }}
            </PrimaryButton>
        </template>
    </DialogModal>
</template>
