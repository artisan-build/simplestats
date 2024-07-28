<script setup>
import InputLabel from "@/Components/InputLabel.vue";
import _ from "lodash";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const appEnv = import.meta.env.VITE_APP_ENV;
</script>


<template>
    <div v-if="appEnv !== 'production'" class="ml-3">
        <InputLabel class="hidden" for="refresh" value="&nbsp;" />
        <SecondaryButton type="submit" style="margin-top:3px;" class="py-3 bg-red-50 hover:bg-red-50 cursor-default text-red-600 border-red-600 focus:ring-0">
            <span class="ml-2">{{ appEnv }}</span>
        </SecondaryButton>
    </div>
</template>
