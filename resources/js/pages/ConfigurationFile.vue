<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
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
</script>

<template>
    <OperatorPage :title="title">
        <form
            class="flex gap-3"
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

            <button class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white" type="submit">
                Create
            </button>
        </form>

        <table class="w-full border-collapse">
            <thead>
                <tr>
                    <th class="border px-3 py-2 text-left">File Name</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="configurationFile in props.configuration_files" :key="configurationFile.config_id">
                    <td class="border px-3 py-2">{{ configurationFile.config_file }}</td>
                </tr>
            </tbody>
        </table>
    </OperatorPage>
</template>
