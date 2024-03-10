<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SectionBorder from "@/Components/SectionBorder.vue";
import ActionTable from "@/Components/ActionTable.vue";
import CreateDataImportForm from "@/Pages/DataImports/Partials/CreateDataImportForm.vue";
import DataImportListRow from "@/Pages/DataImports/Partials/DataImportListRow.vue";

defineProps({
    filters: Object,
    projects: Array,
    instances: Array,
    dataImports: Object,
    importTypes: Array,
    mimeTypes: Array,
    permissions: Object,
});

</script>

<template>
    <AppLayout title="Data Imports">
        <template #header>
            Data Imports
        </template>

        <div>
            <div>
                <CreateDataImportForm
                    :filters="filters"
                    :projects="projects"
                    :instances="instances"
                    :dataImports="dataImports"
                    :importTypes="importTypes"
                    :mimeTypes="mimeTypes"
                    :permissions="permissions"
                />

                <div v-if="dataImports.total > 0">
                    <SectionBorder />

                    <div class="mt-10 sm:mt-0">
                        <ActionTable :links="dataImports.links" >
                            <template #title>
                                Your Data Imports
                            </template>

                            <template #description>
                                Manage your existing Data Imports.
                            </template>

                            <template #content>
                                <tr class="text-left font-bold">
                                    <th class="pb-4 pt-6 px-6">Status</th>
                                    <th class="pb-4 pt-6 px-6">Project</th>
                                    <th class="pb-4 pt-6 px-6">Instance</th>
                                    <th class="pb-4 pt-6 px-6">Type</th>
                                    <th class="pb-4 pt-6 px-6">Creator</th>
                                    <th class="pb-4 pt-6 px-6">Rows</th>
                                    <th class="pb-4 pt-6 px-6">From Id</th>
                                    <th class="pb-4 pt-6 px-6">To Id</th>
                                </tr>
                                <tr v-for="dataImport in dataImports.data" :key="dataImport.id">
                                    <DataImportListRow :dataImport="dataImport" />
                                </tr>
                            </template>
                        </ActionTable>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
