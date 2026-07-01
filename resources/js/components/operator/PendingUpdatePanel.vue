<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import type { RouteDefinition } from '@/wayfinder';
import EmptyState from './EmptyState.vue';
import StatusChip from './StatusChip.vue';

type PendingUpdateItem = {
    gatewaySn: string;
    gatewayMac: string;
    description?: string | null;
    siteCode?: string | null;
    lastLogUpdate?: string | null;
    status: 'online' | 'stale' | 'offline';
    pendingFlags: Array<{
        key: string;
        label: string;
        resetHref: string;
    }>;
    reviewHref: string | RouteDefinition<'get'>;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        items: PendingUpdateItem[];
        emptyTitle?: string;
        emptyDescription?: string;
    }>(),
    {
        title: 'Pending Update Panel',
        description: 'Expose CSV, location, and force-LP flags with direct reset access and a maintenance review path.',
        emptyTitle: 'No pending gateway updates',
        emptyDescription: 'Current RTU update flags will appear here when gateway follow-up is needed.',
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
                    :key="item.gatewayMac"
                    class="flex h-full flex-col gap-3 rounded-lg border p-4"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <StatusChip
                            :label="statusLabel[item.status]"
                            :tone="statusTone[item.status]"
                        />
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            {{ formatRelativeAge(item.lastLogUpdate) }}
                        </p>
                        <p v-if="item.siteCode" class="text-xs font-medium uppercase tracking-wide text-muted-foreground/80">
                            {{ item.siteCode }}
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
                            {{ formatTimestamp(item.lastLogUpdate) }}
                        </p>
                        <p class="text-sm leading-6 text-muted-foreground">
                            {{ item.gatewayMac }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <StatusChip
                            v-for="flag in item.pendingFlags"
                            :key="`${item.gatewayMac}-${flag.key}`"
                            :label="flag.label"
                            tone="warning"
                        />
                    </div>

                    <div class="mt-auto flex flex-wrap gap-2 pt-1">
                        <Link
                            :href="item.reviewHref"
                            class="inline-flex items-center rounded-md border px-3 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                        >
                            Review gateway
                        </Link>

                        <a
                            v-for="flag in item.pendingFlags"
                            :key="`${item.gatewayMac}-${flag.key}-reset`"
                            :href="flag.resetHref"
                            target="_blank"
                            rel="noreferrer"
                            class="inline-flex items-center rounded-md border px-3 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                        >
                            Reset {{ flag.label }}
                        </a>
                    </div>
                </article>
            </div>
        </CardContent>
    </Card>
</template>
