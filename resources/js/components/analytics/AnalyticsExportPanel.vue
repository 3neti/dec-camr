<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

type ExportPanelContext = {
    hasData: boolean;
    periodLabel: string;
    meterIdentifier: string | null;
    buildingCode: string | null;
    siteCode: string | null;
    grain: string;
};

type ExportPanelEvidence = {
    consumptionPointCount: number;
    demandPointCount: number;
    buildingSummaryCount: number;
    incompleteCount: number;
    unknownCount: number;
};

type ExportPanelAction = {
    id: string;
    label: string;
    description: string;
    href: string;
    primary?: boolean;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        context: ExportPanelContext;
        evidence: ExportPanelEvidence;
        actions: ExportPanelAction[];
        preservationNote?: string;
    }>(),
    {
        title: 'Evidence Export Panel',
        description: 'Share the current analytical context while keeping formal workbook exports in Reports.',
        preservationNote: 'Analytics explains evidence. Reports remain the approved workflow for formal XLSX and workbook exports.',
    },
);

const scopeItems = computed(() => [
    { label: 'Period', value: props.context.periodLabel },
    { label: 'Site', value: props.context.siteCode ?? 'No site context' },
    { label: 'Building', value: props.context.buildingCode ?? 'No building context' },
    { label: 'Meter', value: props.context.meterIdentifier ?? 'No meter context' },
    { label: 'Grain', value: props.context.grain },
]);

const evidenceItems = computed(() => [
    { label: 'Consumption', value: props.evidence.consumptionPointCount },
    { label: 'Demand', value: props.evidence.demandPointCount },
    { label: 'Buildings', value: props.evidence.buildingSummaryCount },
    { label: 'Review Flags', value: props.evidence.incompleteCount + props.evidence.unknownCount },
]);
</script>

<template>
    <Card class="gap-4 overflow-hidden py-5">
        <CardHeader class="gap-3 px-5 pb-0">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-1">
                    <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-muted-foreground/80">
                        Export
                    </p>
                    <CardTitle>{{ props.title }}</CardTitle>
                    <CardDescription class="max-w-2xl">
                        {{ props.description }}
                    </CardDescription>
                </div>

                <span
                    class="inline-flex w-fit rounded-full border px-2.5 py-1 text-xs font-medium"
                    :class="props.context.hasData
                        ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                        : 'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-300'"
                >
                    {{ props.context.hasData ? 'Evidence Ready' : 'No Evidence Yet' }}
                </span>
            </div>
        </CardHeader>

        <CardContent class="space-y-5 px-5">
            <div class="rounded-xl border bg-background/70 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    Current Evidence Scope
                </p>

                <dl class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                    <div v-for="item in scopeItems" :key="item.label" class="space-y-1">
                        <dt class="text-xs font-medium text-muted-foreground">{{ item.label }}</dt>
                        <dd class="text-sm font-medium leading-5 text-foreground">{{ item.value }}</dd>
                    </div>
                </dl>
            </div>

            <div class="grid gap-3 md:grid-cols-4">
                <article v-for="item in evidenceItems" :key="item.label" class="rounded-xl border bg-background/70 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ item.label }}</p>
                    <p class="mt-1 text-xl font-semibold text-foreground">{{ item.value }}</p>
                </article>
            </div>

            <div class="grid gap-3 lg:grid-cols-2">
                <Link
                    v-for="action in props.actions"
                    :key="action.id"
                    :href="action.href"
                    class="rounded-xl border p-4 transition hover:border-emerald-500/40 hover:bg-emerald-500/5 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                    :class="action.primary ? 'border-emerald-500/30 bg-emerald-500/5' : 'bg-background/70'"
                >
                    <span class="text-sm font-semibold text-foreground">{{ action.label }}</span>
                    <span class="mt-2 block text-sm leading-6 text-muted-foreground">{{ action.description }}</span>
                </Link>
            </div>

            <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-4 text-sm leading-6 text-amber-800 dark:text-amber-200">
                {{ props.preservationNote }}
            </div>
        </CardContent>
    </Card>
</template>
