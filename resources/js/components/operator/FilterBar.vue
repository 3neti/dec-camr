<script setup lang="ts">
import { computed } from 'vue';
import { Input } from '@/components/ui/input';

type FilterOption = {
    label: string;
    value: string;
};

const props = withDefaults(
    defineProps<{
        query?: string;
        status?: string;
        scope?: string;
        queryPlaceholder?: string;
        queryLabel?: string;
        statusLabel?: string;
        scopeLabel?: string;
        statusOptions?: FilterOption[];
        scopeOptions?: FilterOption[];
        resultsLabel?: string;
    }>(),
    {
        query: '',
        status: 'all',
        scope: 'all',
        queryPlaceholder: 'Search records',
        queryLabel: 'Search',
        statusLabel: 'Status',
        scopeLabel: 'Scope',
        statusOptions: () => [],
        scopeOptions: () => [],
        resultsLabel: '',
    },
);

const emit = defineEmits<{
    'update:query': [value: string];
    'update:status': [value: string];
    'update:scope': [value: string];
}>();

const hasStatus = computed(() => props.statusOptions.length > 0);
const hasScope = computed(() => props.scopeOptions.length > 0);
</script>

<template>
    <div class="flex flex-col gap-3 rounded-lg border bg-muted/30 p-4">
        <div class="grid gap-3 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)]">
            <label class="space-y-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ props.queryLabel }}</span>
                <Input
                    :model-value="props.query"
                    :placeholder="props.queryPlaceholder"
                    @update:model-value="emit('update:query', String($event))"
                />
            </label>

            <label v-if="hasStatus" class="space-y-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ props.statusLabel }}</span>
                <select
                    :value="props.status"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs transition outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                    @change="emit('update:status', ($event.target as HTMLSelectElement).value)"
                >
                    <option v-for="option in props.statusOptions" :key="`status-${option.value}`" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
            </label>

            <label v-if="hasScope" class="space-y-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ props.scopeLabel }}</span>
                <select
                    :value="props.scope"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs transition outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                    @change="emit('update:scope', ($event.target as HTMLSelectElement).value)"
                >
                    <option v-for="option in props.scopeOptions" :key="`scope-${option.value}`" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
            </label>
        </div>

        <p v-if="props.resultsLabel" class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
            {{ props.resultsLabel }}
        </p>
    </div>
</template>
