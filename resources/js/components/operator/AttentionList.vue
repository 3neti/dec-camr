<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import type { RouteDefinition } from '@/wayfinder';
import EmptyState from './EmptyState.vue';
import StatusChip from './StatusChip.vue';

type AttentionSeverity = 'critical' | 'warning' | 'info';

type AttentionItem = {
    id: string | number;
    title: string;
    description: string;
    severity: AttentionSeverity;
    context?: string;
    href?: string | RouteDefinition<'get'>;
    actionLabel?: string;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        items: AttentionItem[];
        emptyTitle?: string;
        emptyDescription?: string;
    }>(),
    {
        title: 'Attention Queue',
        description: 'Review the most urgent operational follow-ups first.',
        emptyTitle: 'No operator attention required',
        emptyDescription: 'Current alerts and follow-up tasks will appear here when action is needed.',
    },
);

const severityOrder: Record<AttentionSeverity, number> = {
    critical: 0,
    warning: 1,
    info: 2,
};

const severityTone = {
    critical: 'danger',
    warning: 'warning',
    info: 'neutral',
} as const;

const severityLabel = {
    critical: 'Critical',
    warning: 'Warning',
    info: 'Info',
} as const;

const sortedItems = computed(() =>
    [...props.items].sort((left, right) => severityOrder[left.severity] - severityOrder[right.severity]),
);
</script>

<template>
    <Card class="gap-4 py-5">
        <CardHeader class="gap-1 px-5 pb-0">
            <div class="space-y-1">
                <h2 class="text-base font-semibold text-foreground">
                    {{ props.title }}
                </h2>
                <p class="text-sm text-muted-foreground">
                    {{ props.description }}
                </p>
            </div>
        </CardHeader>

        <CardContent class="px-5">
            <EmptyState
                v-if="sortedItems.length === 0"
                :title="props.emptyTitle"
                :description="props.emptyDescription"
            />

            <ul v-else class="divide-y rounded-lg border">
                <li
                    v-for="item in sortedItems"
                    :key="item.id"
                    class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <StatusChip
                                :label="severityLabel[item.severity]"
                                :tone="severityTone[item.severity]"
                            />
                            <p v-if="item.context" class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                {{ item.context }}
                            </p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-foreground">
                                {{ item.title }}
                            </p>
                            <p class="text-sm leading-6 text-muted-foreground">
                                {{ item.description }}
                            </p>
                        </div>
                    </div>

                    <div v-if="item.href" class="shrink-0">
                        <Link
                            :href="item.href"
                            class="inline-flex items-center rounded-md border px-3 py-2 text-sm font-medium text-foreground transition hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            {{ item.actionLabel ?? 'Open' }}
                        </Link>
                    </div>
                </li>
            </ul>
        </CardContent>
    </Card>
</template>
