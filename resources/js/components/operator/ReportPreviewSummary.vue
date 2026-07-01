<script setup lang="ts">
import EmptyState from '@/components/operator/EmptyState.vue';
import StatusChip from '@/components/operator/StatusChip.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

type SummaryMetric = {
    label: string;
    value: string;
};

const props = defineProps<{
    title: string;
    description?: string;
    statusLabel: string;
    metrics: SummaryMetric[];
    emptyTitle: string;
    emptyDescription: string;
    notes?: string[];
}>();
</script>

<template>
    <Card class="py-5">
        <CardHeader class="gap-3 px-5 pb-0">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-1">
                    <CardTitle>{{ props.title }}</CardTitle>
                    <CardDescription v-if="props.description">{{ props.description }}</CardDescription>
                </div>

                <StatusChip :label="props.statusLabel" tone="neutral" />
            </div>
        </CardHeader>

        <CardContent class="space-y-6 px-5">
            <dl class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="metric in props.metrics"
                    :key="metric.label"
                    class="rounded-xl border bg-background p-4"
                >
                    <dt class="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground">
                        {{ metric.label }}
                    </dt>
                    <dd class="mt-2 text-sm font-semibold leading-6 text-foreground">
                        {{ metric.value }}
                    </dd>
                </div>
            </dl>

            <EmptyState :title="props.emptyTitle" :description="props.emptyDescription" />

            <div v-if="props.notes?.length" class="space-y-2">
                <h3 class="text-sm font-semibold text-foreground">Operator notes</h3>
                <ul class="space-y-2 text-sm leading-6 text-muted-foreground">
                    <li v-for="note in props.notes" :key="note" class="rounded-lg border bg-muted/30 px-3 py-2">
                        {{ note }}
                    </li>
                </ul>
            </div>
        </CardContent>
    </Card>
</template>
