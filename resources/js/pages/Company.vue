<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import EntityForm from '@/components/operator/EntityForm.vue';
import EntityTable from '@/components/operator/EntityTable.vue';
import OperatorPage from '@/components/operator/OperatorPage.vue';
import { company_info, delete_company_confirmed } from '@/routes';

type Company = {
    company_id: number;
    company_name: string;
    company_code: string | null;
};

const props = defineProps<{
    companies: Company[];
    title: string;
}>();

const page = usePage();
const csrfToken = page.props.csrfToken as string;

const formFields = [
    {
        id: 'company_name',
        name: 'company_name',
        label: 'Company Name',
        placeholder: 'Company Name',
    },
];

const columns = [
    {
        key: 'company_code',
        label: 'Code',
        render: (value: unknown) => String(value ?? '-'),
    },
    {
        key: 'company_name',
        label: 'Name',
    },
];

const rowActions = (company: Company) => [
    {
        id: `inspect-company-${company.company_id}`,
        label: 'Inspect',
        form: company_info.form(),
        fields: { CompanyID: company.company_id },
        target: '_blank' as const,
    },
    {
        id: `delete-company-${company.company_id}`,
        label: 'Delete',
        tone: 'danger' as const,
        form: delete_company_confirmed.form(),
        fields: { CompanyID: company.company_id },
        confirm: `Delete company ${company.company_name}?`,
    },
];
</script>

<template>
    <OperatorPage :title="title">
        <EntityForm
            title="Create company"
            description="Add a new company record using the legacy contract fields."
            action="/create_company_post"
            :fields="formFields"
            :csrf-token="csrfToken"
            grid-class="grid gap-4 sm:grid-cols-2"
        />

        <EntityTable
            title="Existing companies"
            description="Current records in the system."
            :columns="columns"
            :rows="props.companies"
            row-key="company_id"
            :row-actions="rowActions"
            :csrf-token="csrfToken"
        />
    </OperatorPage>
</template>
