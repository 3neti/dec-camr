<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import EmptyState from './EmptyState.vue';

type EntityRow = Record<string, unknown>;

type EntityColumn = {
    key: string;
    label: string;
    className?: string;
    render?: (value: unknown, row: EntityRow) => unknown;
};

const props = withDefaults(
    defineProps<{
        title: string;
        description?: string;
        columns: EntityColumn[];
        rows: EntityRow[];
        rowKey: keyof EntityRow;
        emptyMessage?: string;
        emptyTitle?: string;
    }>(),
    {
        description: '',
        emptyMessage: 'No records found.',
        emptyTitle: 'No data available',
    },
);


const hasRows = computed(() => props.rows.length > 0);


const displayValue = (column: EntityColumn, row: EntityRow): string => {
    const value = row[column.key];
    const rendered = column.render ? column.render(value, row) : value;

    if (rendered === null || rendered === undefined || rendered === '') {
        return '—';
    }

    return String(rendered);
};
</script>

<template>
    <Card>
        <CardHeader class="space-y-1">
            <CardTitle>{{ props.title }}</CardTitle>
            <CardDescription>{{ props.description }}</CardDescription>
        </CardHeader>
        <CardContent>
            <div v-if="!hasRows">
                <EmptyState :title="props.emptyTitle" :description="props.emptyMessage" />
            </div>
            <div v-else class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr>
                            <th v-for="column in props.columns" :key="`${props.title}-${column.key}`" class="border px-3 py-2 text-left">
                                {{ column.label }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in props.rows" :key="String(row[props.rowKey])">
                            <td
                                v-for="column in props.columns"
                                :key="`${String(row[props.rowKey])}-${column.key}`"
                                :class="column.className ?? 'border px-3 py-2'"
                            >
                                {{ displayValue(column, row) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </CardContent>
    </Card>
</template>
