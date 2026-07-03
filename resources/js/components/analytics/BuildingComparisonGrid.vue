<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent, CardHeader } from '@/components/ui/card';

type TrustLevel = 'Measured' | 'Calculated' | 'Estimated' | 'Incomplete' | 'Unknown';

type BuildingConsumptionSummary = {
    buildingId: number;
    buildingCode: string;
    buildingName: string;
    site?: {
        siteId?: number | null;
        siteCode?: string | null;
    };
    totalKwh: number;
    meterCount: number;
    seriesPointCount: number;
    missingData: {
        missingIntervalCount: number;
        incompleteSeriesPointCount: number;
        unknownSeriesPointCount: number;
    };
    comparison?: {
        rankBasis?: string;
        isTopConsumer?: boolean;
        topConsumerKwh?: number | null;
    };
    confidence: {
        level: TrustLevel;
        reason?: string;
    };
};

type RankedSummary = BuildingConsumptionSummary & {
    rank: number;
    percentOfTop: number;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        summaries: BuildingConsumptionSummary[];
        unit?: string;
        emptyLabel?: string;
        sourceLabel?: string;
    }>(),
    {
        title: 'Building Comparison',
        description: 'Rank buildings by calculated consumption while keeping missing-data and confidence visible.',
        unit: 'kWh',
        emptyLabel: 'No building consumption summaries are available for this selection.',
        sourceLabel: 'BuildingConsumptionSummary',
    },
);

const formatNumber = (value: number) => new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(value);

const sortedSummaries = computed<RankedSummary[]>(() => {
    const sorted = [...props.summaries].sort((left, right) => right.totalKwh - left.totalKwh);
    const top = Math.max(...sorted.map((summary) => summary.totalKwh), 0);

    return sorted.map((summary, index) => ({
        ...summary,
        rank: index + 1,
        percentOfTop: top > 0 ? Math.round((summary.totalKwh / top) * 100) : 0,
    }));
});

const topSummary = computed(() => sortedSummaries.value[0] ?? null);
const totalKwh = computed(() => sortedSummaries.value.reduce((total, summary) => total + summary.totalKwh, 0));
const incompleteCount = computed(() => sortedSummaries.value.filter((summary) => summary.confidence.level === 'Incomplete').length);
const unknownCount = computed(() => sortedSummaries.value.filter((summary) => summary.confidence.level === 'Unknown').length);
const missingIntervalCount = computed(() => sortedSummaries.value.reduce((total, summary) => total + summary.missingData.missingIntervalCount, 0));
const hasRows = computed(() => sortedSummaries.value.length > 0);

const confidenceClasses = (level: TrustLevel) => {
    if (level === 'Measured' || level === 'Calculated') {
        return 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    if (level === 'Estimated' || level === 'Unknown') {
        return 'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-300';
    }

    if (level === 'Incomplete') {
        return 'border-rose-500/20 bg-rose-500/10 text-rose-700 dark:text-rose-300';
    }

    return 'border-border bg-muted text-muted-foreground';
};
</script>

<template>
    <Card class="gap-4 overflow-hidden py-5">
        <CardHeader class="gap-3 px-5 pb-0">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-1">
                    <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-muted-foreground/80">
                        Comparison
                    </p>
                    <h3 class="text-base font-semibold text-foreground">
                        {{ props.title }}
                    </h3>
                    <p class="max-w-2xl text-sm leading-6 text-muted-foreground">
                        {{ props.description }}
                    </p>
                </div>

                <span class="inline-flex w-fit items-center rounded-full border bg-muted/60 px-2.5 py-1 text-xs font-medium text-muted-foreground">
                    Source: {{ props.sourceLabel }}
                </span>
            </div>
        </CardHeader>

        <CardContent class="space-y-5 px-5">
            <div class="grid gap-3 md:grid-cols-3">
                <div class="rounded-xl border bg-background/70 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Compared Buildings</p>
                    <p class="mt-1 text-xl font-semibold text-foreground">{{ sortedSummaries.length }}</p>
                </div>
                <div class="rounded-xl border bg-background/70 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total Consumption</p>
                    <p class="mt-1 text-xl font-semibold text-foreground">{{ formatNumber(totalKwh) }} {{ props.unit }}</p>
                </div>
                <div class="rounded-xl border bg-background/70 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Top Consumer</p>
                    <p class="mt-1 text-xl font-semibold text-foreground">{{ topSummary?.buildingCode ?? 'Unavailable' }}</p>
                </div>
            </div>

            <div v-if="hasRows" class="grid gap-3 md:hidden">
                <article
                    v-for="summary in sortedSummaries"
                    :key="`building-card-${summary.buildingId}`"
                    class="rounded-xl border bg-background/70 p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-1">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Rank {{ summary.rank }}</p>
                            <h4 class="text-base font-semibold text-foreground">{{ summary.buildingCode }}</h4>
                            <p class="text-sm text-muted-foreground">{{ summary.buildingName }}</p>
                        </div>
                        <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium" :class="confidenceClasses(summary.confidence.level)">
                            {{ summary.confidence.level }}
                        </span>
                    </div>

                    <div class="mt-4 space-y-3">
                        <div>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="text-muted-foreground">{{ formatNumber(summary.totalKwh) }} {{ props.unit }}</span>
                                <span class="font-medium text-foreground">{{ summary.percentOfTop }}%</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-muted">
                                <div class="h-full rounded-full bg-emerald-600" :style="{ width: `${summary.percentOfTop}%` }" />
                            </div>
                        </div>

                        <dl class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Meters</dt>
                                <dd class="text-foreground">{{ summary.meterCount }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Series</dt>
                                <dd class="text-foreground">{{ summary.seriesPointCount }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Missing</dt>
                                <dd class="text-foreground">{{ summary.missingData.missingIntervalCount }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Site</dt>
                                <dd class="text-foreground">{{ summary.site?.siteCode ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                </article>
            </div>

            <div v-if="hasRows" class="hidden overflow-x-auto rounded-xl border md:block">
                <table class="w-full min-w-[54rem] table-auto text-sm">
                    <caption class="sr-only">
                        {{ props.title }} ranked by total calculated consumption.
                    </caption>
                    <thead class="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th class="px-3 py-3 text-left" scope="col">Rank</th>
                            <th class="px-3 py-3 text-left" scope="col">Building</th>
                            <th class="px-3 py-3 text-left" scope="col">Site</th>
                            <th class="px-3 py-3 text-right" scope="col">Total</th>
                            <th class="px-3 py-3 text-right" scope="col">Meters</th>
                            <th class="px-3 py-3 text-right" scope="col">Missing</th>
                            <th class="px-3 py-3 text-left" scope="col">Confidence</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="summary in sortedSummaries" :key="`building-row-${summary.buildingId}`" class="align-top">
                            <td class="px-3 py-3 font-medium text-foreground">{{ summary.rank }}</td>
                            <td class="px-3 py-3">
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-medium text-foreground">{{ summary.buildingCode }}</span>
                                        <span v-if="summary.comparison?.isTopConsumer" class="rounded-full bg-amber-500/10 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300">
                                            Top consumer
                                        </span>
                                    </div>
                                    <p class="text-muted-foreground">{{ summary.buildingName }}</p>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-muted-foreground">{{ summary.site?.siteCode ?? '—' }}</td>
                            <td class="px-3 py-3 text-right">
                                <div class="space-y-2">
                                    <p class="font-medium text-foreground">{{ formatNumber(summary.totalKwh) }} {{ props.unit }}</p>
                                    <div class="ml-auto h-2 max-w-32 overflow-hidden rounded-full bg-muted">
                                        <div class="h-full rounded-full bg-emerald-600" :style="{ width: `${summary.percentOfTop}%` }" />
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-right text-muted-foreground">{{ summary.meterCount }}</td>
                            <td class="px-3 py-3 text-right text-muted-foreground">{{ summary.missingData.missingIntervalCount }}</td>
                            <td class="px-3 py-3">
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium" :class="confidenceClasses(summary.confidence.level)">
                                    {{ summary.confidence.level }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!hasRows" class="rounded-2xl border border-dashed bg-muted/30 p-6 text-center">
                <p class="text-sm font-medium text-foreground">{{ props.emptyLabel }}</p>
                <p class="mt-2 text-sm leading-6 text-muted-foreground">
                    Select a valid time range with scoped building summaries before comparing buildings.
                </p>
            </div>

            <div v-if="incompleteCount > 0 || unknownCount > 0 || missingIntervalCount > 0" class="rounded-xl border bg-background/70 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Data Quality</p>
                <div class="mt-2 flex flex-wrap gap-2 text-xs font-medium">
                    <span v-if="incompleteCount > 0" class="rounded-full bg-rose-500/10 px-2.5 py-1 text-rose-700 dark:text-rose-300">
                        Incomplete buildings: {{ incompleteCount }}
                    </span>
                    <span v-if="unknownCount > 0" class="rounded-full bg-amber-500/10 px-2.5 py-1 text-amber-700 dark:text-amber-300">
                        Unknown buildings: {{ unknownCount }}
                    </span>
                    <span v-if="missingIntervalCount > 0" class="rounded-full bg-muted px-2.5 py-1 text-muted-foreground">
                        Missing intervals: {{ missingIntervalCount }}
                    </span>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
