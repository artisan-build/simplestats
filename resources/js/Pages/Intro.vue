<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import {FormWizard,TabContent} from "vue3-form-wizard";
import 'vue3-form-wizard/dist/style.css';
import {useForm} from "@inertiajs/vue3";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import SelectInput from "@/Components/SelectInput.vue";
import TrackingUsageSelector from "@/Components/TrackingUsageSelector.vue";

const props = defineProps({
    team: String,
    timezones: Array,
    trackingUsages: Object,
});

const form = useForm({
    team: props.team,
    currency: '',
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone ?? 'UTC',
    project: '',
    instance: 'Default',
    usage: 0,
});

const submit = () => {
    form.post(route('intro'));
};

const appName = import.meta.env.VITE_APP_NAME;
</script>

<template>

    <AppLayout title="Intro">
        <div class="flex justify-center pt-20">
            <div class="w-3/4">
                <h1 class="text-4xl font-medium mb-20 text-center">Just a few more steps...</h1>
                <div v-if="form.errors" class="mb-5 text-center">
                    <InputError v-for="error in form.errors" :message="error+'&nbsp;'" class="inline-block" />
                </div>
                <FormWizard color="#716bdd" @on-complete="submit" step-size="xs">
                    <TabContent class="pt-10 pb-20 text-center" title="Name">
                        <div class="text-xl font-medium mb-10">
                            What is the name of your team?
                        </div>

                        <TextInput
                            id="team"
                            v-model="form.team"
                            type="text"
                            class="mt-1 inline-block w-2/5"
                        />
                    </TabContent>
                    <TabContent class="pt-10 pb-20 text-center" title="Currency">
                        <div class="text-xl font-medium mb-10">
                            What is the currency of your team/company?
                            <p class="text-base mt-2">
                                All tracked multi-currency payments will be converted to your currency at the daily exchange rate.
                            </p>
                        </div>

                        <TextInput
                            id="currency"
                            v-model="form.currency"
                            type="text"
                            class="mt-1 inline-block w-2/5"
                            placeholder="e.g. USD"
                        />
                    </TabContent>
                    <TabContent class="pt-10 pb-20 text-center" title="Timezone">
                        <div class="text-xl font-medium mb-10">
                            What is the timezone of your team?
                        </div>

                        <SelectInput
                            id="timezone"
                            v-model="form.timezone"
                            class="mt-1 inline-block w-2/5"
                            :options="timezones"
                        />
                    </TabContent>
                    <TabContent class="pt-10 pb-20 text-center" title="Project">
                        <div class="text-xl font-medium mb-10">
                            What should your first project be called?
                        </div>

                        <TextInput
                            id="project"
                            v-model="form.project"
                            type="text"
                            class="mt-1 inline-block w-2/5"
                            placeholder="e.g. My great SaaS"
                        />
                    </TabContent>
<!--                    <TabContent class="pt-10 pb-20 text-center" title="Instance">-->
<!--                        <div class="text-xl font-medium mb-10">-->
<!--                            What should your first instance be called? (optional)-->
<!--                            <p class="text-base mt-2">-->
<!--                                You can divide a project into multiple instances.-->
<!--                                For example, in a SaaS application, you might have different environments like “Environment A,” “Environment B,” etc. This allows you to view and analyze each instance separately or get an aggregated overview at the project level.-->
<!--                            </p>-->
<!--                        </div>-->

<!--                        <TextInput-->
<!--                            id="project"-->
<!--                            v-model="form.instance"-->
<!--                            type="text"-->
<!--                            class="mt-1 inline-block w-2/5"-->
<!--                        />-->
<!--                    </TabContent>-->
                    <TabContent class="pt-10 pb-20 text-center" title="Usage">
                        <div class="text-xl font-medium mb-10">
                            What do you want to track?
                            <p class="text-base mt-2">
                                Select all options that fit your needs!
                            </p>
                        </div>

                        <div class="w-10/12 m-auto">
                            <TrackingUsageSelector :trackingUsages="trackingUsages" v-model="form.usage" border="4" />
                            <div class="text-sm mt-5">
                                In most cases, it’s best to select all options…
                            </div>
                        </div>
                    </TabContent>
                </FormWizard>
            </div>
        </div>
    </AppLayout>
</template>

<style>
.wizard-footer-left {
    display: none;
}
</style>
