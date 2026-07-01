<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import EntityForm from '@/components/operator/EntityForm.vue';
import EntityTable from '@/components/operator/EntityTable.vue';
import OperatorPage from '@/components/operator/OperatorPage.vue';

type Meter = {
    meter_id: number;
    meter_name: string;
    meter_default_name: string;
    meter_status: string;
};

const props = defineProps<{
    meters?: Meter[];
    title: string;
}>();

const page = usePage();
const csrfToken = page.props.csrfToken as string;

const formFields = [
    {
        id: 'meter_name',
        name: 'meter_name',
        label: 'Meter Name',
        placeholder: 'MTR-001',
    },
    {
        id: 'meter_default_name',
        name: 'meter_default_name',
        label: 'Alternate Address',
        placeholder: 'Alternate Name',
    },
    {
        id: 'meter_model_id',
        name: 'meter_model_id',
        label: 'Configuration',
        placeholder: '1',
    },
    {
        id: 'rtu_sn_number_id',
        name: 'rtu_sn_number_id',
        label: 'Gateway ID',
        placeholder: '1',
    },
    {
        id: 'location_id',
        name: 'location_id',
        label: 'Location ID',
        placeholder: '1',
    },
    {
        id: 'site_id',
        name: 'siteID',
        hidden: true,
        value: 1,
    },
    {
        id: 'site_code',
        name: 'site_code',
        hidden: true,
        value: 'SITEA',
    },
    {
        id: 'meter_name_addressable',
        name: 'meter_name_addressable',
        hidden: true,
        value: 1,
    },
    {
        id: 'meter_multiplier',
        name: 'meter_multiplier',
        hidden: true,
        value: 1,
    },
    {
        id: 'meter_type',
        name: 'meter_type',
        hidden: true,
        value: 'Power',
    },
    {
        id: 'meter_brand',
        name: 'meter_brand',
        hidden: true,
        value: 'Schneider',
    },
    {
        id: 'meter_role',
        name: 'meter_role',
        hidden: true,
        value: 'Client Meter',
    },
    {
        id: 'meter_status',
        name: 'meter_status',
        hidden: true,
        value: 'ACTIVE',
    },
    {
        id: 'customer_name',
        name: 'customer_name',
        hidden: true,
        value: '',
    },
    {
        id: 'meter_remarks',
        name: 'meter_remarks',
        hidden: true,
        value: '',
    },
];

const columns = [
    {
        key: 'meter_name',
        label: 'Name',
    },
    {
        key: 'meter_default_name',
        label: 'Alternate',
    },
    {
        key: 'meter_status',
        label: 'Status',
    },
];
</script>

<template>
    <OperatorPage :title="title">
        <EntityForm
            title="Create meter"
            description="Preserve legacy creation contract with clearer operator field layout."
            action="/create_meter_post"
            :fields="formFields"
            :csrf-token="csrfToken"
            grid-class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
            submit-wrapper-class="flex items-end sm:col-span-2 lg:col-span-3"
        />

        <EntityTable
            title="Existing meters"
            description="Current records in the system."
            :columns="columns"
            :rows="props.meters ?? []"
            row-key="meter_id"
            :filter-bar="{
                queryPlaceholder: 'Search meter name, alternate name, or status',
                queryKeys: ['meter_name', 'meter_default_name', 'meter_status'],
                statusKey: 'meter_status',
            }"
        />
    </OperatorPage>
</template>
