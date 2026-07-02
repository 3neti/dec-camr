<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import EntityTable from '@/components/operator/EntityTable.vue';
import OperatorPage from '@/components/operator/OperatorPage.vue';

type ConfigurationFile = {
    config_id: number;
    config_file: string;
};

const props = defineProps<{
    configuration_files: ConfigurationFile[];
    title: string;
}>();

const page = usePage();
const csrfToken = page.props.csrfToken as string;

const columns = [
    {
        key: 'config_file',
        label: 'File Name',
    },
];
</script>

<template>
    <OperatorPage :title="title">
        <form
            class="grid max-w-2xl gap-4 rounded-xl border bg-card p-5 shadow-sm sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end"
            method="POST"
            action="/create_configuration_file_post"
        >
            <input type="hidden" name="_token" :value="csrfToken" />

            <label class="flex flex-col gap-1">
                <span class="text-sm font-medium">File Name</span>
                <input
                    class="h-9 rounded-md border px-3"
                    type="text"
                    name="configuration_file_name"
                    placeholder="Configuration file name"
                />
            </label>

            <button class="inline-flex w-full items-center justify-center rounded-md bg-black px-4 py-2 text-sm font-medium text-white sm:w-auto" type="submit">
                Create
            </button>
        </form>

        <EntityTable
            title="Existing configuration files"
            description="Legacy configuration filenames available to gateway and meter workflows."
            :columns="columns"
            :rows="props.configuration_files"
            row-key="config_id"
        />
    </OperatorPage>
</template>
