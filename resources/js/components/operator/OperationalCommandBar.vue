<script setup lang="ts">
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import EmptyState from './EmptyState.vue';
import StatusChip from './StatusChip.vue';

type CommandTone = 'neutral' | 'success' | 'warning' | 'danger';

type CommandItem = {
    key: string;
    title: string;
    description: string;
    enabled: boolean;
    disabledReason: string | null;
    href: string;
};

type CommandTarget = {
    gatewaySn: string;
    gatewayMac: string;
    description: string | null;
    siteCode: string | null;
    status: 'online' | 'stale' | 'offline';
};

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        target: CommandTarget | null;
        commands: CommandItem[];
        emptyTitle?: string;
        emptyDescription?: string;
    }>(),
    {
        title: 'Operational Command Bar',
        description: 'Approved RTU-safe commands for the current command target. Commands open the live protocol endpoints so payloads and responses remain unchanged.',
        emptyTitle: 'No command target available',
        emptyDescription: 'Seed gateways or run the simulator to expose an operational command target.',
    },
);

const statusTone: Record<CommandTarget['status'], CommandTone> = {
    online: 'success',
    stale: 'warning',
    offline: 'danger',
};

const statusLabel = {
    online: 'Online',
    stale: 'Stale',
    offline: 'Offline',
} as const;
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

        <CardContent class="space-y-4 px-5">
            <EmptyState
                v-if="props.target === null"
                :title="props.emptyTitle"
                :description="props.emptyDescription"
            />

            <template v-else>
                <div class="flex flex-col gap-3 rounded-lg border p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <StatusChip
                            :label="statusLabel[props.target.status]"
                            :tone="statusTone[props.target.status]"
                        />
                        <p v-if="props.target.siteCode" class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                            {{ props.target.siteCode }}
                        </p>
                    </div>

                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-foreground">
                            {{ props.target.gatewaySn }}
                            <span v-if="props.target.description" class="font-normal text-muted-foreground">
                                • {{ props.target.description }}
                            </span>
                        </p>
                        <p class="text-sm leading-6 text-muted-foreground">
                            {{ props.target.gatewayMac }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 xl:grid-cols-2">
                    <article
                        v-for="command in props.commands"
                        :key="command.key"
                        class="flex h-full flex-col gap-3 rounded-lg border p-4"
                    >
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-foreground">
                                {{ command.title }}
                            </p>
                            <p class="text-sm leading-6 text-muted-foreground">
                                {{ command.description }}
                            </p>
                        </div>

                        <p
                            v-if="!command.enabled && command.disabledReason"
                            :id="`${command.key}-reason`"
                            class="text-sm leading-6 text-muted-foreground"
                        >
                            {{ command.disabledReason }}
                        </p>

                        <div class="mt-auto pt-1">
                            <a
                                :href="command.href"
                                target="_blank"
                                rel="noreferrer"
                                class="inline-flex items-center rounded-md border px-3 py-2 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                :class="command.enabled ? 'border-border text-foreground hover:bg-muted' : 'cursor-not-allowed border-border/60 text-muted-foreground opacity-60 pointer-events-none'"
                                :aria-disabled="command.enabled ? 'false' : 'true'"
                                :aria-describedby="!command.enabled && command.disabledReason ? `${command.key}-reason` : undefined"
                                :aria-label="`${command.title} for gateway ${props.target.gatewaySn}`"
                            >
                                {{ command.enabled ? 'Open command' : 'Unavailable' }}
                            </a>
                        </div>
                    </article>
                </div>
            </template>
        </CardContent>
    </Card>
</template>
