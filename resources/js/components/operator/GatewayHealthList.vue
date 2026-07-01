<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import type { RouteDefinition } from '@/wayfinder';
import EmptyState from './EmptyState.vue';
import StatusChip from './StatusChip.vue';

type GatewayStatus = 'online' | 'stale' | 'offline';

type GatewayHealthItem = {
    id: string | number;
    gatewaySn: string;
    gatewayMac: string;
    description?: string | null;
    siteCode?: string | null;
    lastLogUpdate?: string | null;
    status: GatewayStatus;
    softRev?: string | null;
    meterCount: number;
    activeMeterCount: number;
    pendingUpdates: string[];
    hasPendingUpdates: boolean;
    href?: string | RouteDefinition<'get'>;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        items: GatewayHealthItem[];
        emptyTitle?: string;
        emptyDescription?: string;
    }>(),
    {
        title: 'Gateway Health',
        description: 'Track online, stale, offline, and pending-update gateways before issues spread into reports or maintenance drift.',
        emptyTitle: 'No gateways available',
        emptyDescription: 'Seed the demo profile or add gateways to populate the live operations fleet view.',
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

function formatTimestamp(value: string | null | undefined): string {
    if (!value) {
        return 'No communication recorded';
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

function pluralize(value: number): string {
    return value === 1 ? '' : 's';
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
                            <StatusChip
                                v-if="item.hasPendingUpdates"
                                :label="`${item.pendingUpdates.length} pending`"
                                tone="warning"
                            />
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                {{ formatRelativeAge(item.lastLogUpdate) }}
                            </p>
                        </div>

                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-foreground">
                                {{ item.gatewaySn }}
                                <span v-if="item.description" class="font-normal text-muted-foreground">
                                    • {{ item.description }}
                                </span>
                            </p>
                            <p class="text-sm leading-6 text-muted-foreground">
                                {{ item.siteCode ?? 'No site code' }}
                                <span> • {{ item.activeMeterCount }} active meter{{ pluralize(item.activeMeterCount) }}</span>
                                <span> • {{ item.meterCount }} total meter{{ pluralize(item.meterCount) }}</span>
                            </p>
                            <p class="text-sm leading-6 text-muted-foreground">
                                {{ formatTimestamp(item.lastLogUpdate) }}
                                <span v-if="item.softRev"> • Soft rev {{ item.softRev }}</span>
                                <span v-if="item.pendingUpdates.length > 0"> • {{ item.pendingUpdates.join(', ') }}</span>
                            </p>
                            <p class="text-xs uppercase tracking-wide text-muted-foreground/80">
                                {{ item.gatewayMac }}
                            </p>
                        </div>
                    </div>

                    <div v-if="item.href" class="shrink-0">
                        <Link
                            :href="item.href"
                            class="inline-flex items-center rounded-md border px-3 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                        >
                            Open gateway
                        </Link>
                    </div>
                </li>
            </ul>
        </CardContent>
    </Card>
</template>
