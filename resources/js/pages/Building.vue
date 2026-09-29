<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ContextHeader from '@/components/operator/ContextHeader.vue';
import DrilldownActions from '@/components/operator/DrilldownActions.vue';
import EntityForm from '@/components/operator/EntityForm.vue';
import EntityTable from '@/components/operator/EntityTable.vue';
import HierarchyBreadcrumb from '@/components/operator/HierarchyBreadcrumb.vue';
import OperatorPage from '@/components/operator/OperatorPage.vue';
import RelationshipCard from '@/components/operator/RelationshipCard.vue';
import { DeleteBuildingInfo, building_info, gateway, site } from '@/routes';

type Building = {
    building_id: number;
    site_idx: number;
    building_code: string | null;
    building_description: string | null;
};

type Row = Building & {
    action?: string;
};

const props = defineProps<{
    buildings: Building[] | Row[];
    title: string;
}>();

const page = usePage();
const csrfToken = page.props.csrfToken as string;
const searchParams = computed(() => new URL(page.url, 'http://camr.local').searchParams);
const selectedSiteID = computed(() => searchParams.value.get('siteID'));
const selectedSiteLabel = computed(() => searchParams.value.get('site') ?? 'Selected site');
const selectedSiteCode = computed(() => searchParams.value.get('siteCode'));

const buildingCards = computed(() => {
    if (!selectedSiteID.value) {
        return props.buildings;
    }

    return props.buildings.filter((buildingRow) => String(buildingRow.site_idx) === selectedSiteID.value);
});

const buildingLabel = (buildingRow: Building | Row): string => buildingRow.building_description ?? buildingRow.building_code ?? 'Unnamed building';

const buildingMeta = (buildingRow: Building | Row): string[] => [
    `Site ${selectedSiteLabel.value}`,
    `Reference ${buildingRow.building_code ?? 'Unavailable'}`,
];

const buildingActions = (buildingRow: Building | Row) => [
    {
        label: 'View gateways',
        href: gateway.url({
            query: {
                siteID: buildingRow.site_idx,
                site: selectedSiteLabel.value,
                siteCode: selectedSiteCode.value ?? '',
                buildingCode: buildingRow.building_code ?? '',
                building: buildingLabel(buildingRow),
            },
        }),
        primary: true,
    },
];

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
        key: 'building_description',
        label: 'Building',
        render: (value: unknown, row: Record<string, unknown>) => String(value ?? row.building_code ?? '-'),
    },
    {
        key: 'building_code',
        label: 'Reference',
        render: (value: unknown) => String(value ?? '-'),
    },
];

const rowActions = (buildingRow: Building | Row) => [
    {
        id: `inspect-building-${buildingRow.building_id}`,
        label: 'Inspect',
        form: building_info.form(),
        fields: { buildingID: buildingRow.building_id },
        target: '_blank' as const,
    },
    {
        id: `delete-building-${buildingRow.building_id}`,
        label: 'Delete',
        tone: 'danger' as const,
        form: DeleteBuildingInfo.form(),
        fields: { buildingID: buildingRow.building_id },
        confirm: `Delete building ${buildingRow.building_code ?? buildingRow.building_id}?`,
    },
];
</script>

<template>
    <OperatorPage :title="title">
        <div class="space-y-6">
            <HierarchyBreadcrumb :items="[{ label: 'Sites', href: site.url() }, { label: selectedSiteID ? selectedSiteLabel : 'All buildings' }]" />

            <ContextHeader
                eyebrow="Building hierarchy"
                :title="selectedSiteID ? `Buildings for ${selectedSiteLabel}` : 'Buildings'"
                description="Choose a building to see its gateway layer. The maintenance form and legacy action table remain below the navigation cards."
                :meta="[`${buildingCards.length} buildings shown`, selectedSiteID ? 'Filtered by selected site' : 'Portfolio view']"
            />

            <section class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3" aria-label="Building navigation cards">
                <RelationshipCard
                    v-for="buildingRow in buildingCards"
                    :key="buildingRow.building_id"
                    eyebrow="Building"
                    :title="buildingLabel(buildingRow)"
                    :description="'Open gateways serving this building context.'"
                    :meta="buildingMeta(buildingRow)"
                >
                    <DrilldownActions :actions="buildingActions(buildingRow)" />
                </RelationshipCard>
            </section>

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
                description="Current records in the system. Use View gateways for hierarchy navigation."
                :columns="columns"
                :rows="buildingCards"
                row-key="building_id"
                :row-actions="rowActions"
                :csrf-token="csrfToken"
            />
        </div>
    </OperatorPage>
</template>
