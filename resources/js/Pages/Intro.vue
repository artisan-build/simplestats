<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import {FormWizard,TabContent} from "vue3-form-wizard";
import 'vue3-form-wizard/dist/style.css';
import {useForm} from "@inertiajs/vue3";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    team: String,
});

const form = useForm({
    team: props.team,
    currency: '',
    project: '',
    instance: 'Default',
});

const submit = () => {
    form.post(route('intro'));
};
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
                            What is the name of your team/company?
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
                                All tracked multi-currency payments of your app will be converted to your currency at the daily exchange rate.
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
                    <TabContent class="pt-10 pb-20 text-center" title="Instance">
                        <div class="text-xl font-medium mb-10">
                            What should your first instance be called? (optional)
                            <p class="text-base mt-2">
                                You can divide a project into multiple instances.
                                For example, in a gaming app, you might have multiple game worlds per app like "Game World 1", "Game World 2", etc.<br>
                                This allows you to filter and manage them separately. If you do not plan to use multiple instances, you can just leave this as "Default".
                            </p>
                        </div>

                        <TextInput
                            id="project"
                            v-model="form.instance"
                            type="text"
                            class="mt-1 inline-block w-2/5"
                        />
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
