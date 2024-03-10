<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import SectionBorder from "@/Components/SectionBorder.vue";
import ActionTable from "@/Components/ActionTable.vue";
import CreateDataImportForm from "@/Pages/DataImports/Partials/CreateDataImportForm.vue";
import DataImportListRow from "@/Pages/DataImports/Partials/ShowDataImportRow.vue";

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
                                    <th class="pb-2 pt-3 px-3">Status</th>
                                    <th class="pb-2 pt-3 px-3">Project</th>
                                    <th class="pb-2 pt-3 px-3">Instance</th>
<!--                                    <th class="pb-2 pt-3 px-3">Type</th>-->
                                    <th class="pb-2 pt-3 px-3">Creator</th>
                                    <th class="pb-2 pt-3 px-3">Rows</th>
                                    <th class="pb-2 pt-3 px-3">From Id</th>
                                    <th class="pb-2 pt-3 px-3">To Id</th>
                                    <th class="pb-2 pt-3 px-3">Action</th>
                                </tr>
                                <tr v-for="dataImport in dataImports.data" :key="dataImport.id">
                                    <DataImportListRow :projects="projects" :instances="instances" :dataImport="dataImport" />
                                </tr>
                            </template>
                        </ActionTable>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
