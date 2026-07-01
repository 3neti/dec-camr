<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import EntityForm from '@/components/operator/EntityForm.vue';
import EntityTable from '@/components/operator/EntityTable.vue';
import OperatorPage from '@/components/operator/OperatorPage.vue';

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
        />
    </OperatorPage>
</template>
