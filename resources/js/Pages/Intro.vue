<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import {FormWizard,TabContent} from "vue3-form-wizard";
import 'vue3-form-wizard/dist/style.css';
import {useForm} from "@inertiajs/vue3";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    team: String,
});

const form = useForm({
    team: props.team,
    currency: '',
    project: '',
    instance: '',
});

const submit = () => {
    form.post(route('intro'));
};
</script>

<template>

    <AppLayout title="Projects">
        <div class="flex justify-center pt-20">
            <div class="w-3/4">
                <h1 class="text-4xl font-medium mb-20 text-center">Just a few more steps...</h1>
                <div v-if="form.errors" class="mb-5">
                    <InputError v-for="error in form.errors" :message="error+'&nbsp;'" class="inline-block" />
                </div>
                <FormWizard color="#716bdd" @on-complete="submit" step-size="xs">
                    <TabContent class="pt-10 pb-20 text-center" title="Name">
                        <div class="text-xl font-medium mb-10">What is the name of your Team/Company?</div>

                        <TextInput
                            id="team"
                            v-model="form.team"
                            type="text"
                            class="mt-1 inline-block w-3/5"
                        />
                    </TabContent>
                    <TabContent class="pt-10 pb-20 text-center" title="Currency">
                        <div class="text-xl font-medium mb-10">
                            What is the currency of your Team/Company?<br>
                            All tracked multi-currency payments are getting converted to this currency on a daily exchange rate.
                        </div>

                        <TextInput
                            id="currency"
                            v-model="form.currency"
                            type="text"
                            class="mt-1 inline-block w-3/5"
                            placeholder="USD"
                        />
                    </TabContent>
                    <TabContent class="pt-10 pb-20 text-center" title="Project">
                        <div class="text-xl font-medium mb-10">How should your first Project be called?</div>

                        <TextInput
                            id="project"
                            v-model="form.project"
                            type="text"
                            class="mt-1 inline-block w-3/5"
                            placeholder="My great SaaS"
                        />
                    </TabContent>
                    <TabContent class="pt-10 pb-20 text-center" title="Instance">
                        <div class="text-xl font-medium mb-10">
                            Each of your Projects may contain multiple Instances.<br>
                            What should your first Instance be called?
                        </div>

                        <TextInput
                            id="project"
                            v-model="form.instance"
                            type="text"
                            class="mt-1 inline-block w-3/5"
                            placeholder="US East"
                        />
                    </TabContent>
                </FormWizard>
            </div>
        </div>
    </AppLayout>
</template>
