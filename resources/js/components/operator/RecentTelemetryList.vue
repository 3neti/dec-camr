<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import type { RouteDefinition } from '@/wayfinder';
import EmptyState from './EmptyState.vue';
import ScopePill from './ScopePill.vue';
import StatusChip from './StatusChip.vue';

type TelemetryStatus = 'online' | 'stale' | 'offline';

type RecentTelemetryItem = {
    id: string | number;
    meterId: string;
    meterName?: string | null;
    siteCode?: string | null;
    locationId?: string | null;
    receivedAt: string;
    status: TelemetryStatus;
    href?: string | RouteDefinition<'get'>;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        items: RecentTelemetryItem[];
        emptyTitle?: string;
        emptyDescription?: string;
    }>(),
    {
        title: 'Recent Telemetry',
        description: 'Latest readings flowing through the current operational window.',
        emptyTitle: 'No telemetry received',
        emptyDescription: 'Run the simulator or wait for device traffic to populate recent readings here.',
    },
);

const statusTone = {
    online: 'success',
    stale: 'warning',
    offline: 'danger',
} as const;

const statusLabel = {
    online: 'Online',
    stale: 'Stale',
    offline: 'Offline',
} as const;

function formatMeterLabel(item: RecentTelemetryItem): string {
    if (item.meterName && item.meterName.trim() !== '') {
        return item.meterName;
    }

    return `Meter ${item.meterId}`;
}

function formatRelativeAge(value: string): string {
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

function formatTimestamp(value: string): string {
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

            <ul v-else class="divide-y rounded-lg border">
                <li
                    v-for="item in props.items"
                    :key="item.id"
                    class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <StatusChip
                                :label="statusLabel[item.status]"
                                :tone="statusTone[item.status]"
                            />
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                {{ formatRelativeAge(item.receivedAt) }}
                            </p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-foreground">
                                {{ formatMeterLabel(item) }}
                            </p>
                            <p class="text-sm leading-6 text-muted-foreground">
                                {{ formatTimestamp(item.receivedAt) }}
                                <span v-if="item.locationId"> • Location {{ item.locationId }}</span>
                            </p>
                            <div v-if="item.siteCode" class="flex flex-wrap items-center gap-2">
                                <ScopePill label="Site" :value="item.siteCode" tone="info" />
                            </div>
                        </div>
                    </div>

                    <div v-if="item.href" class="shrink-0">
                        <Link
                            :href="item.href"
                            class="inline-flex items-center rounded-md border px-3 py-2 text-sm font-medium text-foreground transition hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            :aria-label="`Open meter ${item.meterId}`"
                        >
                            Open meter
                        </Link>
                    </div>
                </li>
            </ul>
        </CardContent>
    </Card>
</template>
