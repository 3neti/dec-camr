<script setup lang="ts">
import { computed } from 'vue';
import EmptyState from '@/components/operator/EmptyState.vue';
import StatusChip from '@/components/operator/StatusChip.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

type DownloadShelfEntry = {
    id: string;
    reportFamily: string;
    filename: string;
    status: 'Complete';
    generatedAt: string;
    fileType: string;
    filterSummary: string;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        emptyTitle?: string;
        emptyDescription?: string;
        entries: DownloadShelfEntry[];
    }>(),
    {
        title: 'Download shelf',
        description: 'Track recent report exports generated in this browser session.',
        emptyTitle: 'No downloads in this session yet',
        emptyDescription: 'Completed report exports will appear here once an analyst downloads a workbook.',
    },
);

const orderedEntries = computed(() => [...props.entries].sort((left, right) => right.generatedAt.localeCompare(left.generatedAt)));

const formatTimestamp = (value: string): string => {
    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? value : date.toLocaleString();
};

const formatFileType = (value: string): string => {
    if (value.includes('spreadsheetml')) {
        return 'XLSX';
    }

    if (value.trim() === '') {
        return 'File';
    }

    return value;
};
</script>

<template>
    <Card class="py-5">
        <CardHeader class="px-5 pb-0">
            <CardTitle>{{ props.title }}</CardTitle>
            <CardDescription>{{ props.description }}</CardDescription>
        </CardHeader>

        <CardContent class="px-5">
            <EmptyState
                v-if="orderedEntries.length === 0"
                :title="props.emptyTitle"
                :description="props.emptyDescription"
            />

            <ul v-else class="divide-y rounded-lg border">
                <li
                    v-for="entry in orderedEntries"
                    :key="entry.id"
                    class="space-y-3 px-4 py-4"
                >
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-foreground">
                                {{ entry.filename }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ entry.reportFamily }} • {{ entry.filterSummary }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <StatusChip :label="entry.status" tone="success" />
                            <span class="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground">
                                {{ formatFileType(entry.fileType) }}
                            </span>
                        </div>
                    </div>

                    <p class="text-xs leading-5 text-muted-foreground">
                        {{ formatTimestamp(entry.generatedAt) }}
                    </p>
                </li>
            </ul>
        </CardContent>
    </Card>
</template>
