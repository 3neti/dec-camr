<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import EntityForm from '@/components/operator/EntityForm.vue';
import EntityTable from '@/components/operator/EntityTable.vue';
import OperatorPage from '@/components/operator/OperatorPage.vue';
import { DeleteGateway, gateway_info } from '@/routes';

type Gateway = {
    rtu_id: number;
    gateway_sn: string;
    gateway_mac: string;
    gateway_ip: string;
    site_code: string | null;
};

const props = defineProps<{
    gateways: Gateway[];
    title: string;
}>();

const page = usePage();
const csrfToken = page.props.csrfToken as string;

const formFields = [
    {
        id: 'gateway_sn',
        name: 'gateway_sn',
        label: 'Gateway Serial Number',
        placeholder: 'GW-001',
    },
    {
        id: 'gateway_mac',
        name: 'gateway_mac',
        label: 'MAC Address',
        placeholder: 'AA:BB:CC:DD:EE:FF',
    },
    {
        id: 'gateway_ip',
        name: 'gateway_ip',
        label: 'IP Address',
        placeholder: '10.0.0.1',
    },
    {
        id: 'site_id',
        name: 'siteID',
        label: 'Site ID',
        hidden: true,
        value: 1,
    },
];

const columns = [
    {
        key: 'gateway_sn',
        label: 'Serial',
    },
    {
        key: 'gateway_mac',
        label: 'MAC',
    },
    {
        key: 'gateway_ip',
        label: 'IP',
    },
    {
        key: 'site_code',
        label: 'Site Code',
        render: (value: unknown) => String(value ?? '—'),
    },
];

const rowActions = (gatewayRow: Gateway) => [
    {
        id: `inspect-gateway-${gatewayRow.rtu_id}`,
        label: 'Inspect',
        form: gateway_info.form(),
        fields: { gatewayID: gatewayRow.rtu_id },
        target: '_blank' as const,
    },
    {
        id: `delete-gateway-${gatewayRow.rtu_id}`,
        label: 'Delete',
        tone: 'danger' as const,
        form: DeleteGateway.form(),
        fields: { gatewayID: gatewayRow.rtu_id },
        confirm: `Delete gateway ${gatewayRow.gateway_sn}?`,
    },
];
</script>

<template>
    <OperatorPage :title="title">
        <EntityForm
            title="Create gateway"
            description="Keep legacy field names and defaults while modernizing layout."
            action="/create_gateway_post"
            :fields="[
                ...formFields,
                { id: 'site_code', name: 'site_code', hidden: true, value: 'SITEA' },
                { id: 'connection_type', name: 'connection_type', hidden: true, value: 'LAN' },
                { id: 'location_id', name: 'location_id', hidden: true, value: 0 },
            ]"
            :csrf-token="csrfToken"
            grid-class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
            submit-wrapper-class="flex items-end sm:col-span-2 lg:col-span-4"
        />

        <EntityTable
            title="Existing gateways"
            description="Current records in the system."
            :columns="columns"
            :rows="props.gateways"
            row-key="rtu_id"
            :filter-bar="{
                queryPlaceholder: 'Search serial, MAC, IP, or site code',
                queryKeys: ['gateway_sn', 'gateway_mac', 'gateway_ip', 'site_code'],
                scopeKey: 'site_code',
            }"
            :row-actions="rowActions"
            :csrf-token="csrfToken"
        />
    </OperatorPage>
</template>
