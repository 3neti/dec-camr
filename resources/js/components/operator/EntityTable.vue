<script setup lang="ts">
import { computed, ref } from 'vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import type { RouteDefinition, RouteFormDefinition } from '@/wayfinder';
import EmptyState from './EmptyState.vue';
import EntityActions from './EntityActions.vue';
import FilterBar from './FilterBar.vue';

type EntityRow = Record<string, unknown>;

type EntityColumn = {
    key: string;
    label: string;
    className?: string;
    render?: (value: unknown, row: EntityRow) => unknown;
};

type FilterBarOption = {
    label: string;
    value: string;
};

type FilterBarConfig = {
    queryPlaceholder?: string;
    queryKeys?: string[];
    statusKey?: string;
    statusOptions?: FilterBarOption[];
    scopeKey?: string;
    scopeOptions?: FilterBarOption[];
};

type EntityRowAction = {
    id: string;
    label: string;
    tone?: 'neutral' | 'danger';
    href?: string | RouteDefinition<'get'>;
    form?: RouteFormDefinition<'get' | 'post'>;
    fields?: Record<string, string | number>;
    target?: '_self' | '_blank';
    confirm?: string;
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
        filterBar?: FilterBarConfig;
        rowActions?: (row: any) => EntityRowAction[];
        csrfToken?: string;
    }>(),
    {
        description: '',
        emptyMessage: 'No records found.',
        emptyTitle: 'No data available',
        filterBar: undefined,
        rowActions: undefined,
        csrfToken: '',
    },
);

const query = ref('');
const status = ref('all');
const scope = ref('all');

const derivedStatusOptions = computed<FilterBarOption[]>(() => {
    if (! props.filterBar?.statusKey) {
        return [];
    }

    if (props.filterBar.statusOptions && props.filterBar.statusOptions.length > 0) {
        return props.filterBar.statusOptions;
    }

    const values = Array.from(
        new Set(
            props.rows
                .map((row) => String(row[props.filterBar?.statusKey ?? ''] ?? '').trim())
                .filter((value) => value !== ''),
        ),
    ).sort((left, right) => left.localeCompare(right));

    return [
        { label: 'All statuses', value: 'all' },
        ...values.map((value) => ({ label: value, value })),
    ];
});

const derivedScopeOptions = computed<FilterBarOption[]>(() => {
    if (! props.filterBar?.scopeKey) {
        return [];
    }

    if (props.filterBar.scopeOptions && props.filterBar.scopeOptions.length > 0) {
        return props.filterBar.scopeOptions;
    }

    const values = Array.from(
        new Set(
            props.rows
                .map((row) => String(row[props.filterBar?.scopeKey ?? ''] ?? '').trim())
                .filter((value) => value !== ''),
        ),
    ).sort((left, right) => left.localeCompare(right));

    return [
        { label: 'All scopes', value: 'all' },
        ...values.map((value) => ({ label: value, value })),
    ];
});

const filteredRows = computed(() => {
    const search = query.value.trim().toLowerCase();
    const queryKeys = props.filterBar?.queryKeys ?? props.columns.map((column) => column.key);

    return props.rows.filter((row) => {
        if (search !== '') {
            const matchesQuery = queryKeys.some((key) => String(row[key] ?? '').toLowerCase().includes(search));

            if (! matchesQuery) {
                return false;
            }
        }

        if (props.filterBar?.statusKey && status.value !== 'all') {
            if (String(row[props.filterBar.statusKey] ?? '') !== status.value) {
                return false;
            }
        }

        if (props.filterBar?.scopeKey && scope.value !== 'all') {
            if (String(row[props.filterBar.scopeKey] ?? '') !== scope.value) {
                return false;
            }
        }

        return true;
    });
});

const hasRows = computed(() => filteredRows.value.length > 0);
const hasAnyRows = computed(() => props.rows.length > 0);
const hasRowActions = computed(() => typeof props.rowActions === 'function');
const filterResultLabel = computed(() => {
    if (! props.filterBar) {
        return '';
    }

    return `${filteredRows.value.length} of ${props.rows.length} records visible`;
});
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
        <CardContent class="space-y-4">
            <FilterBar
                v-if="props.filterBar"
                v-model:query="query"
                v-model:status="status"
                v-model:scope="scope"
                :query-placeholder="props.filterBar.queryPlaceholder ?? 'Search records'"
                :status-options="derivedStatusOptions"
                :scope-options="derivedScopeOptions"
                :results-label="filterResultLabel"
            />

            <div v-if="!hasRows">
                <EmptyState
                    :title="hasAnyRows ? 'No matching records' : props.emptyTitle"
                    :description="hasAnyRows ? 'Adjust the current filters or search terms to broaden the visible records.' : props.emptyMessage"
                />
            </div>
            <div v-else class="space-y-4">
                <div class="grid gap-3 md:hidden">
                    <article
                        v-for="row in filteredRows"
                        :key="`mobile-${String(row[props.rowKey])}`"
                        class="rounded-lg border bg-background p-4 shadow-sm"
                    >
                        <dl class="space-y-3">
                            <div
                                v-for="column in props.columns"
                                :key="`${String(row[props.rowKey])}-mobile-${column.key}`"
                                class="space-y-1"
                            >
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    {{ column.label }}
                                </dt>
                                <dd class="text-sm leading-6 text-foreground break-words">
                                    {{ displayValue(column, row) }}
                                </dd>
                            </div>
                        </dl>

                        <div v-if="hasRowActions" class="mt-4 border-t pt-4">
                            <EntityActions
                                orientation="column"
                                :actions="props.rowActions?.(row) ?? []"
                                :csrf-token="props.csrfToken"
                            />
                        </div>
                    </article>
                </div>

                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full min-w-[40rem] border-collapse">
                    <thead>
                        <tr>
                            <th v-for="column in props.columns" :key="`${props.title}-${column.key}`" class="border px-3 py-2 text-left">
                                {{ column.label }}
                            </th>
                            <th v-if="hasRowActions" class="border px-3 py-2 text-right">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in filteredRows" :key="String(row[props.rowKey])">
                            <td
                                v-for="column in props.columns"
                                :key="`${String(row[props.rowKey])}-${column.key}`"
                                :class="column.className ?? 'border px-3 py-2'"
                            >
                                {{ displayValue(column, row) }}
                            </td>
                            <td v-if="hasRowActions" class="border px-3 py-2 align-top">
                                <EntityActions
                                    orientation="row"
                                    :actions="props.rowActions?.(row) ?? []"
                                    :csrf-token="props.csrfToken"
                                />
                            </td>
                        </tr>
                    </tbody>
                    </table>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
