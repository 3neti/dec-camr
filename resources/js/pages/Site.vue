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
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { building, company, delete_site_confirmed, division, site, site_details, site_info } from '@/routes';

type Site = {
    site_id: number;
    site_code: string | null;
    building_code: string | null;
    building_description: string | null;
    division_idx: number;
    company_idx: number;
};

type ViewSite = {
    site_id: number;
    site_code: string | null;
    building_description: string | null;
    division_idx: number;
    company_idx: number;
};

const props = defineProps<{
    sites: Site[];
    title: string;
    viewSite?: ViewSite | null;
}>();

const page = usePage();
const csrfToken = page.props.csrfToken as string;

const siteCards = computed(() => props.sites ?? []);

const siteLabel = (siteRow: Site): string => siteRow.building_description ?? siteRow.building_code ?? siteRow.site_code ?? 'Unnamed site';

const siteReference = (siteRow: Site): string => siteRow.building_code ?? siteRow.site_code ?? 'Uncoded site';

const siteMeta = (siteRow: Site): string[] => [
    `Division ${siteRow.division_idx}`,
    `Company ${siteRow.company_idx}`,
    `Reference ${siteReference(siteRow)}`,
];

const siteActions = (siteRow: Site) => [
    {
        label: 'View buildings',
        href: building.url({
            query: {
                siteID: siteRow.site_id,
                site: siteLabel(siteRow),
                siteCode: siteRow.site_code ?? siteRow.building_code ?? '',
            },
        }),
        primary: true,
    },
    {
        label: 'Company context',
        href: company.url({ query: { companyID: siteRow.company_idx } }),
    },
    {
        label: 'Division context',
        href: division.url({ query: { divisionID: siteRow.division_idx } }),
    },
    {
        label: 'Details',
        href: site_details.url(siteRow.site_id),
    },
];

const formFields = [
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
        placeholder: 'Building Description',
    },
    {
        id: 'division_id',
        name: 'division_id',
        label: 'Division ID',
        placeholder: '1',
    },
    {
        id: 'company_id',
        name: 'company_id',
        label: 'Company ID',
        placeholder: '1',
    },
];

const columns = [
    {
        key: 'building_description',
        label: 'Site',
        render: (value: unknown, row: Record<string, unknown>) => String(value ?? row.building_code ?? row.site_code ?? '-'),
    },
    {
        key: 'building_code',
        label: 'Reference',
        render: (value: unknown, row: Record<string, unknown>) => String(value ?? row.site_code ?? '-'),
    },
    {
        key: 'division_idx',
        label: 'Division',
    },
    {
        key: 'company_idx',
        label: 'Company',
    },
];

const rowActions = (siteRow: Site) => [
    {
        id: `view-site-${siteRow.site_id}`,
        label: 'View',
        href: site_details({ siteID: siteRow.site_id }),
    },
    {
        id: `inspect-site-${siteRow.site_id}`,
        label: 'Inspect',
        form: site_info.form(),
        fields: { siteID: siteRow.site_id },
        target: '_blank' as const,
    },
    {
        id: `delete-site-${siteRow.site_id}`,
        label: 'Delete',
        tone: 'danger' as const,
        form: delete_site_confirmed.form(),
        fields: { siteID: siteRow.site_id },
        confirm: `Delete site ${siteRow.building_code ?? siteRow.site_code ?? siteRow.site_id}?`,
    },
];
</script>

<template>
    <OperatorPage :title="title">
        <div class="space-y-6">
            <HierarchyBreadcrumb :items="[{ label: 'Sites', href: site.url() }]" />

            <ContextHeader
                eyebrow="Operator navigation"
                title="Sites"
                description="Start from a site, then drill into buildings, gateways, meters, and historical readings without hunting through raw IDs. Legacy maintenance actions remain available below."
                :meta="['Site → Building → Gateway → Meter → Readings', `${siteCards.length} sites available`]"
            />

            <section class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3" aria-label="Site navigation cards">
                <RelationshipCard
                    v-for="siteRow in siteCards"
                    :key="siteRow.site_id"
                    eyebrow="Site"
                    :title="siteLabel(siteRow)"
                    :description="'Open the operational hierarchy for this site.'"
                    :meta="siteMeta(siteRow)"
                >
                    <DrilldownActions :actions="siteActions(siteRow)" />
                </RelationshipCard>
            </section>

            <EntityForm
                title="Create site"
                description="Legacy create contract: building code/description and owning division/company ids."
                action="/create_site_post"
                :fields="formFields"
                :csrf-token="csrfToken"
                grid-class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
                submit-wrapper-class="flex items-end sm:col-span-2 lg:col-span-4"
            />

            <Card v-if="props.viewSite">
                <CardHeader class="space-y-1">
                    <CardTitle>{{ props.viewSite.building_description || props.viewSite.site_code || 'Site details' }}</CardTitle>
                    <CardDescription>Detailed single-site context from the legacy response.</CardDescription>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-muted-foreground">Reference: {{ props.viewSite.site_code || '—' }}</p>
                    <p class="text-sm text-muted-foreground">Division: {{ props.viewSite.division_idx }}</p>
                    <p class="text-sm text-muted-foreground">Company: {{ props.viewSite.company_idx }}</p>
                </CardContent>
            </Card>

            <EntityTable
                title="Existing sites"
                description="Current records in the system. Use the cards above for normal drilldown navigation."
                :columns="columns"
                :rows="props.sites"
                row-key="site_id"
                :row-actions="rowActions"
                :csrf-token="csrfToken"
            />
        </div>
    </OperatorPage>
</template>
