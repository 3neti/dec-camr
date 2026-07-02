<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import type { RouteDefinition } from '@/wayfinder';
import EmptyState from './EmptyState.vue';
import StatusChip from './StatusChip.vue';

type TimelineSeverity = 'critical' | 'warning' | 'info';
type TimelineResource = 'gateway' | 'meter' | 'telemetry';

type TelemetryTimelineItem = {
    id: string | number;
    eventType: string;
    resource: TimelineResource;
    severity: TimelineSeverity;
    subject: string;
    context?: string | null;
    title: string;
    description: string;
    occurredAt?: string | null;
    href?: string | RouteDefinition<'get'>;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        items: TelemetryTimelineItem[];
        emptyTitle?: string;
        emptyDescription?: string;
    }>(),
    {
        title: 'Telemetry Timeline',
        description: 'Follow the latest telemetry, stale/offline transitions, and pending gateway actions in one operational feed.',
        emptyTitle: 'No timeline events available',
        emptyDescription: 'Run the simulator or seed demo telemetry to populate the operational event feed.',
    },
);

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

const actionLabel = {
    gateway: 'Open gateway',
    meter: 'Open meter',
    telemetry: 'Open telemetry',
} as const;

function formatTimestamp(value: string | null | undefined): string {
    if (!value) {
        return 'No timestamp available';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat(undefined, {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(date);
}

function formatRelativeAge(value: string | null | undefined): string {
    if (!value) {
        return 'No recent telemetry';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    const diffMinutes = Math.round((date.getTime() - Date.now()) / 60000);
    const formatter = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });

    if (Math.abs(diffMinutes) < 60) {
        return formatter.format(diffMinutes, 'minute');
    }

    const diffHours = Math.round(diffMinutes / 60);

    if (Math.abs(diffHours) < 24) {
        return formatter.format(diffHours, 'hour');
    }

    return formatter.format(Math.round(diffHours / 24), 'day');
}
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
                v-if="props.items.length === 0"
                :title="props.emptyTitle"
                :description="props.emptyDescription"
            />

            <ol v-else class="space-y-3">
                <li
                    v-for="item in props.items"
                    :key="item.id"
                    class="flex gap-3 rounded-lg border p-4"
                >
                    <div class="mt-1 shrink-0">
                        <span
                            class="block size-2.5 rounded-full"
                            :class="{
                                'bg-red-500': item.severity === 'critical',
                                'bg-amber-500': item.severity === 'warning',
                                'bg-slate-400': item.severity === 'info',
                            }"
                        />
                    </div>

                    <div class="min-w-0 flex-1 space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <StatusChip
                                :label="severityLabel[item.severity]"
                                :tone="severityTone[item.severity]"
                            />
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                {{ formatRelativeAge(item.occurredAt) }}
                            </p>
                            <p v-if="item.context" class="text-xs font-medium uppercase tracking-wide text-muted-foreground/80">
                                {{ item.context }}
                            </p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-foreground">
                                {{ item.title }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ item.subject }}
                            </p>
                            <p class="text-sm leading-6 text-muted-foreground">
                                {{ item.description }}
                            </p>
                            <p class="text-sm leading-6 text-muted-foreground">
                                {{ formatTimestamp(item.occurredAt) }}
                            </p>
                        </div>
                    </div>

                    <div v-if="item.href" class="shrink-0">
                        <Link
                            :href="item.href"
                            class="inline-flex items-center rounded-md border px-3 py-2 text-sm font-medium text-foreground transition hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            :aria-label="`${actionLabel[item.resource]} ${item.subject}`"
                        >
                            {{ actionLabel[item.resource] }}
                        </Link>
                    </div>
                </li>
            </ol>
        </CardContent>
    </Card>
</template>
