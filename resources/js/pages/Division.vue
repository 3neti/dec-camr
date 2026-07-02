<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import EntityForm from '@/components/operator/EntityForm.vue';
import EntityTable from '@/components/operator/EntityTable.vue';
import OperatorPage from '@/components/operator/OperatorPage.vue';
import { delete_division_confirmed, division_info } from '@/routes';

type Division = {
    division_id: number;
    division_name: string;
    division_code: string | null;
};

const props = defineProps<{
    divisions: Division[];
    title: string;
}>();

const page = usePage();
const csrfToken = page.props.csrfToken as string;

const formFields = [
    {
        id: 'division_code',
        name: 'division_code',
        label: 'Division Code',
        placeholder: 'Division Code',
    },
    {
        id: 'division_name',
        name: 'division_name',
        label: 'Division Name',
        placeholder: 'Division Name',
    },
];

const columns = [
    {
        key: 'division_code',
        label: 'Code',
        render: (value: unknown) => String(value ?? '-'),
    },
    {
        key: 'division_name',
        label: 'Name',
    },
];

const rowActions = (division: Division) => [
    {
        id: `inspect-division-${division.division_id}`,
        label: 'Inspect',
        form: division_info.form(),
        fields: { DivisionID: division.division_id },
        target: '_blank' as const,
    },
    {
        id: `delete-division-${division.division_id}`,
        label: 'Delete',
        tone: 'danger' as const,
        form: delete_division_confirmed.form(),
        fields: { DivisionID: division.division_id },
        confirm: `Delete division ${division.division_name}?`,
    },
];
</script>

<template>
    <OperatorPage :title="title">
        <EntityForm
            title="Create division"
            description="Add a new division within an operating context."
            action="/create_division_post"
            :fields="formFields"
            :csrf-token="csrfToken"
            grid-class="grid gap-4 sm:grid-cols-3"
        />

        <EntityTable
            title="Existing divisions"
            description="Current records in the system."
            :columns="columns"
            :rows="props.divisions"
            row-key="division_id"
            :row-actions="rowActions"
            :csrf-token="csrfToken"
        />
    </OperatorPage>
</template>
