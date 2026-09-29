<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ContextHeader from '@/components/operator/ContextHeader.vue';
import EntityForm from '@/components/operator/EntityForm.vue';
import EntityTable from '@/components/operator/EntityTable.vue';
import HierarchyBreadcrumb from '@/components/operator/HierarchyBreadcrumb.vue';
import OperatorPage from '@/components/operator/OperatorPage.vue';
import ReadingRangeLinks from '@/components/operator/ReadingRangeLinks.vue';
import RelationshipCard from '@/components/operator/RelationshipCard.vue';
import { DeleteMeter, gateway, meter_info, site } from '@/routes';

type Meter = {
    meter_id: number;
    meter_name: string;
    meter_default_name: string | null;
    meter_status: string | null;
    meter_role?: string | null;
    meter_remarks?: string | null;
    meter_multiplier?: string | number | null;
    meter_type?: string | null;
    meter_brand?: string | null;
    gateway_sn?: string | null;
    location_code?: string | null;
    config_file?: string | null;
};

const props = defineProps<{
    meters?: Meter[];
    title: string;
}>();

const page = usePage();
const csrfToken = page.props.csrfToken as string;
const searchParams = computed(() => new URL(page.url, 'http://camr.local').searchParams);
const selectedGatewaySN = computed(() => searchParams.value.get('gatewaySN'));
const selectedSiteLabel = computed(() => searchParams.value.get('site') ?? 'Selected site');
const selectedSiteCode = computed(() => searchParams.value.get('siteCode'));
const selectedBuildingCode = computed(() => searchParams.value.get('buildingCode'));
const selectedBuildingLabel = computed(() => searchParams.value.get('building') ?? selectedBuildingCode.value ?? 'Selected building');

const meterCards = computed(() => {
    const meters = props.meters ?? [];

    if (!selectedGatewaySN.value) {
        return meters;
    }

    return meters.filter((meterRow) => meterRow.gateway_sn === selectedGatewaySN.value);
});

const meterLabel = (meterRow: Meter): string => meterRow.meter_default_name || meterRow.meter_name;

const meterMeta = (meterRow: Meter): string[] => [
    meterRow.meter_status ? `Status ${meterRow.meter_status}` : 'Status unavailable',
    meterRow.meter_role ?? 'Role unavailable',
    meterRow.location_code ? `Location ${meterRow.location_code}` : 'Location unavailable',
];

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
        key: 'meter_default_name',
        label: 'Meter',
        render: (value: unknown, row: Record<string, unknown>) => String(value ?? row.meter_name ?? '-'),
    },
    {
        key: 'gateway_sn',
        label: 'Gateway',
        render: (value: unknown) => String(value ?? '—'),
    },
    {
        key: 'location_code',
        label: 'Location',
        render: (value: unknown) => String(value ?? '—'),
    },
    {
        key: 'meter_status',
        label: 'Status',
        render: (value: unknown) => String(value ?? '—'),
    },
];

const rowActions = (meterRow: Meter) => [
    {
        id: `inspect-meter-${meterRow.meter_id}`,
        label: 'Inspect',
        form: meter_info.form(),
        fields: { meterID: meterRow.meter_id },
        target: '_blank' as const,
    },
    {
        id: `delete-meter-${meterRow.meter_id}`,
        label: 'Delete',
        tone: 'danger' as const,
        form: DeleteMeter.form(),
        fields: { meterID: meterRow.meter_id },
        confirm: `Delete meter ${meterRow.meter_name}?`,
    },
];
</script>

<template>
    <OperatorPage :title="title">
        <div class="space-y-6">
            <HierarchyBreadcrumb
                :items="[
                    { label: 'Sites', href: site.url() },
                    { label: selectedSiteLabel, href: gateway.url({ query: { siteCode: selectedSiteCode ?? '', buildingCode: selectedBuildingCode ?? '', building: selectedBuildingLabel } }) },
                    { label: selectedGatewaySN ?? 'All gateways' },
                ]"
            />

            <ContextHeader
                eyebrow="Meter layer"
                :title="selectedGatewaySN ? `Meters for ${selectedGatewaySN}` : 'Meters'"
                description="Choose a meter reading window to move from maintenance context into analytics. Historical readings open in the Analytics Workbench."
                :meta="[`${meterCards.length} meters shown`, selectedBuildingCode ? `Building ${selectedBuildingLabel}` : 'Portfolio view']"
            />

            <section class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3" aria-label="Meter navigation cards">
                <RelationshipCard
                    v-for="meterRow in meterCards"
                    :key="meterRow.meter_id"
                    eyebrow="Meter"
                    :title="meterLabel(meterRow)"
                    :description="meterRow.meter_remarks || 'Open historical readings for this meter.'"
                    :meta="meterMeta(meterRow)"
                >
                    <ReadingRangeLinks :meter-identifier="meterRow.meter_name" :building-code="selectedBuildingCode" />
                </RelationshipCard>
            </section>

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
                description="Current records in the system. Use reading links above for historical meter analysis."
                :columns="columns"
                :rows="meterCards"
                row-key="meter_id"
                :filter-bar="{
                    queryPlaceholder: 'Search meter, gateway, location, or status',
                    queryKeys: ['meter_name', 'meter_default_name', 'gateway_sn', 'location_code', 'meter_status'],
                    statusKey: 'meter_status',
                }"
                :row-actions="rowActions"
                :csrf-token="csrfToken"
            />
        </div>
    </OperatorPage>
</template>
