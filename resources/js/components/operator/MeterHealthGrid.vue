<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import type { RouteDefinition } from '@/wayfinder';
import EmptyState from './EmptyState.vue';
import StatusChip from './StatusChip.vue';

type MeterStatus = 'online' | 'stale' | 'offline';

type MeterHealthItem = {
    id: string | number;
    meterId: string;
    meterName?: string | null;
    defaultName?: string | null;
    siteCode?: string | null;
    locationId?: string | null;
    lastLogUpdate?: string | null;
    status: MeterStatus;
    meterStatus: string;
    gatewaySn?: string | null;
    gatewayMac?: string | null;
    href?: string | RouteDefinition<'get'>;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        items: MeterHealthItem[];
        emptyTitle?: string;
        emptyDescription?: string;
    }>(),
    {
        title: 'Meter Health',
        description: 'Track stale, offline, and current active meters with enough context to move from observation to maintenance drill-down.',
        emptyTitle: 'No active meters available',
        emptyDescription: 'Seed the demo profile or add active meters to populate the meter health view.',
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

function formatMeterLabel(item: MeterHealthItem): string {
    if (item.meterName && item.meterName.trim() !== '') {
        return item.meterName;
    }

    if (item.defaultName && item.defaultName.trim() !== '') {
        return item.defaultName;
    }

    return `Meter ${item.meterId}`;
}

function formatTimestamp(value: string | null | undefined): string {
    if (!value) {
        return 'No reading recorded';
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

            <div v-else class="grid gap-3 xl:grid-cols-2">
                <article
                    v-for="item in props.items"
                    :key="item.id"
                    class="flex h-full flex-col gap-3 rounded-lg border p-4"
                >
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <StatusChip
                                :label="statusLabel[item.status]"
                                :tone="statusTone[item.status]"
                            />
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                {{ formatRelativeAge(item.lastLogUpdate) }}
                            </p>
                        </div>

                        <span class="text-xs font-medium uppercase tracking-wide text-muted-foreground/80">
                            {{ item.meterStatus }}
                        </span>
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-foreground">
                            {{ formatMeterLabel(item) }}
                        </p>
                        <p class="text-sm leading-6 text-muted-foreground">
                            Meter {{ item.meterId }}
                            <span v-if="item.siteCode"> • {{ item.siteCode }}</span>
                            <span v-if="item.locationId"> • Location {{ item.locationId }}</span>
                        </p>
                        <p class="text-sm leading-6 text-muted-foreground">
                            Last reading {{ formatTimestamp(item.lastLogUpdate) }}
                        </p>
                        <p class="text-sm leading-6 text-muted-foreground">
                            {{ item.gatewaySn ?? 'No gateway assigned' }}
                            <span v-if="item.gatewayMac"> • {{ item.gatewayMac }}</span>
                        </p>
                    </div>

                    <div v-if="item.href" class="mt-auto pt-1">
                        <Link
                            :href="item.href"
                            class="inline-flex items-center rounded-md border px-3 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                        >
                            Open meter
                        </Link>
                    </div>
                </article>
            </div>
        </CardContent>
    </Card>
</template>
