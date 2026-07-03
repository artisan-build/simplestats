<script setup>
import {ref} from 'vue';
import {router, usePage} from '@inertiajs/vue3';
import SelectInput from '@/Components/SelectInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import ProjectFavicon from '@/Components/ProjectFavicon.vue';

defineProps({
    projects: {type: Object, required: true},
    projectId: {type: [Number, String, null], default: null},
});

const emit = defineEmits(['update:projectId', 'project-changed']);

const page = usePage();

const currentTeamId = ref(page.props.auth.user.current_team_id);

const teamChanged = () => {
    router.put(route('current-team.update'), {
        team_id: currentTeamId.value,
    }, {
        preserveState: false,
    });
};
</script>

<template>
    <div class="flex flex-row flex-wrap gap-x-2 gap-y-3 items-center w-full sm:w-auto">
        <div v-if="page.props.auth.user.all_teams.length > 1" class="flex-1 sm:flex-none min-w-0">
            <InputLabel class="hidden" for="teamId" value="Team" />
            <SelectInput
                id="teamId"
                v-model="currentTeamId"
                class="block w-full sm:w-40 dark:!bg-dark-800"
                :options="page.props.auth.user.all_teams"
                @change="teamChanged"
            />
        </div>
        <div class="flex-1 sm:flex-none min-w-0">
            <InputLabel class="hidden" for="project_id" value="Project" />
            <SelectInput
                id="project_id"
                :model-value="projectId"
                class="block w-full sm:w-48 dark:!bg-dark-800"
                :options="projects"
                @update:model-value="emit('update:projectId', $event)"
                @change="emit('project-changed')"
            >
                <template #selected="{ option }">
                    <span class="flex items-center gap-2">
                        <ProjectFavicon :domain="option?.domain" />
                        <span class="truncate">{{ option?.name ?? 'Select...' }}</span>
                    </span>
                </template>
                <template #option="{ option }">
                    <span class="flex items-center gap-2">
                        <ProjectFavicon :domain="option.domain" />
                        <span class="truncate">{{ option.name }}</span>
                    </span>
                </template>
            </SelectInput>
        </div>
    </div>
</template>
