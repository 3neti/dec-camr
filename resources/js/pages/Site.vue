<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import EntityForm from '@/components/operator/EntityForm.vue';
import EntityTable from '@/components/operator/EntityTable.vue';
import OperatorPage from '@/components/operator/OperatorPage.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { delete_site_confirmed, site_details, site_info } from '@/routes';

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
        key: 'building_code',
        label: 'Building Code',
        render: (value: unknown, row: Record<string, unknown>) =>
            String((row.building_code as string | null) ?? (row.site_code as string | null) ?? value ?? '-'),
    },
    {
        key: 'building_description',
        label: 'Description',
        render: (value: unknown) => String(value ?? '-'),
    },
    {
        key: 'division_idx',
        label: 'Division ID',
    },
    {
        key: 'company_idx',
        label: 'Company ID',
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
                <CardTitle>Viewing Site #{{ props.viewSite.site_id }}</CardTitle>
                <CardDescription>Detailed single-site context from the legacy response.</CardDescription>
            </CardHeader>
            <CardContent>
                <p class="text-sm text-muted-foreground">Code: {{ props.viewSite.site_code || '—' }}</p>
                <p class="text-sm text-muted-foreground">Description: {{ props.viewSite.building_description || '—' }}</p>
            </CardContent>
        </Card>

        <EntityTable
            title="Existing sites"
            description="Current records in the system."
            :columns="columns"
            :rows="props.sites"
            row-key="site_id"
            :row-actions="rowActions"
            :csrf-token="csrfToken"
        />
    </OperatorPage>
</template>
