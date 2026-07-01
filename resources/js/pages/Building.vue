<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import EntityForm from '@/components/operator/EntityForm.vue';
import EntityTable from '@/components/operator/EntityTable.vue';
import OperatorPage from '@/components/operator/OperatorPage.vue';

type Building = {
    building_id: number;
    site_idx: number;
    building_code: string | null;
    building_description: string | null;
};

type Row = {
    building_id: number;
    site_idx: number;
    building_code: string | null;
    building_description: string | null;
    action?: string;
};

const props = defineProps<{
    buildings: Building[] | Row[];
    title: string;
}>();

const page = usePage();
const csrfToken = page.props.csrfToken as string;

const formFields = [
    {
        id: 'building_site_id',
        name: 'siteID',
        label: 'Site ID',
        placeholder: '1',
    },
    {
        id: 'building_code',
        name: 'building_code',
        label: 'Building Code',
        placeholder: 'SITEA',
    },
    {
        id: 'building_description',
        name: 'building_description',
        label: 'Building Description',
        placeholder: 'Accessible Building',
    },
];

const columns = [
    {
        key: 'building_id',
        label: 'Building ID',
    },
    {
        key: 'site_idx',
        label: 'Site ID',
    },
    {
        key: 'building_code',
        label: 'Building Code',
        render: (value: unknown) => String(value ?? '-'),
    },
    {
        key: 'building_description',
        label: 'Description',
        render: (value: unknown) => String(value ?? '-'),
    },
];
</script>

<template>
    <OperatorPage :title="title">
        <EntityForm
            title="Create building"
            description="Legacy create contract for a building entry."
            action="/create_building_post"
            :fields="formFields"
            :csrf-token="csrfToken"
            grid-class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
        />

        <EntityTable
            title="Existing buildings"
            description="Current records in the system."
            :columns="columns"
            :rows="props.buildings"
            row-key="building_id"
        />
    </OperatorPage>
</template>
