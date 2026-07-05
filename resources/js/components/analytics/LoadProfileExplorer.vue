<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import ConsumptionTrend from './ConsumptionTrend.vue';
import DemandCurve from './DemandCurve.vue';

type TrustLevel = 'Measured' | 'Calculated' | 'Estimated' | 'Incomplete' | 'Unknown';

type ConsumptionSeriesPoint = {
    periodStart: string;
    periodEnd: string;
    grain: string;
    kwhTotal: number | null;
    confidence: {
        level: TrustLevel;
        reason?: string;
    };
    missingData?: {
        missingIntervalCount?: number;
        missingStartReading?: boolean;
        missingEndReading?: boolean;
    };
};

type DemandSeriesPoint = {
    periodStart: string;
    periodEnd: string;
    grain: string;
    kwDemand: number | null;
    elapsedMinutes?: number | null;
    peakMarker?: {
        isPeak?: boolean;
        peakKwDemand?: number | null;
    };
    confidence: {
        level: TrustLevel;
        reason?: string;
    };
    missingData?: {
        missingIntervalCount?: number;
        missingMinReading?: boolean;
        missingMaxReading?: boolean;
    };
};

type ProfileContext = {
    meterName?: string | null;
    buildingCode?: string | null;
    siteCode?: string | null;
    grain?: string | null;
    periodLabel?: string | null;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        context?: ProfileContext;
        consumptionPoints?: ConsumptionSeriesPoint[];
        demandPoints?: DemandSeriesPoint[];
        emptyLabel?: string;
    }>(),
    {
        title: 'Load Profile Explorer',
        description: 'Inspect consumption shape, demand peaks, and missing intervals for a selected meter or building.',
        context: () => ({}),
        consumptionPoints: () => [],
        demandPoints: () => [],
        emptyLabel: 'Select an analytical subject and time range to inspect its load profile.',
    },
);

const hasConsumption = computed(() => props.consumptionPoints.length > 0);
const hasDemand = computed(() => props.demandPoints.length > 0);
const hasAnySeries = computed(() => hasConsumption.value || hasDemand.value);
const incompleteConsumptionCount = computed(() => props.consumptionPoints.filter((point) => point.confidence.level === 'Incomplete').length);
const incompleteDemandCount = computed(() => props.demandPoints.filter((point) => point.confidence.level === 'Incomplete').length);
const unknownConsumptionCount = computed(() => props.consumptionPoints.filter((point) => point.confidence.level === 'Unknown').length);
const unknownDemandCount = computed(() => props.demandPoints.filter((point) => point.confidence.level === 'Unknown').length);
const missingIntervalCount = computed(() => [
    ...props.consumptionPoints.map((point) => point.missingData?.missingIntervalCount ?? 0),
    ...props.demandPoints.map((point) => point.missingData?.missingIntervalCount ?? 0),
].reduce((total, count) => total + count, 0));

const investigationSteps = computed(() => [
    {
        label: 'Frame',
        description: props.context.periodLabel ?? 'Choose a time range and analytical grain.',
        active: Boolean(props.context.periodLabel),
    },
    {
        label: 'Locate',
        description: [props.context.siteCode, props.context.buildingCode, props.context.meterName].filter(Boolean).join(' / ') || 'Select site, building, or meter context.',
        active: Boolean(props.context.siteCode || props.context.buildingCode || props.context.meterName),
    },
    {
        label: 'Compare Shape',
        description: hasConsumption.value ? 'Consumption series is available.' : 'Consumption series is not available yet.',
        active: hasConsumption.value,
    },
    {
        label: 'Inspect Peak',
        description: hasDemand.value ? 'Demand series is available.' : 'Demand series is not available yet.',
        active: hasDemand.value,
    },
]);
</script>

<template>
    <section class="space-y-5" aria-labelledby="analytics-load-profile-title">
        <Card class="gap-4 overflow-hidden py-5">
            <CardHeader class="gap-3 px-5 pb-0">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-1">
                        <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-muted-foreground/80">
                            Investigation
                        </p>
                        <h3 id="analytics-load-profile-title" class="text-base font-semibold text-foreground">
                            {{ props.title }}
                        </h3>
                        <p class="max-w-2xl text-sm leading-6 text-muted-foreground">
                            {{ props.description }}
                        </p>
                    </div>

                    <span class="inline-flex w-fit items-center rounded-full border bg-muted/60 px-2.5 py-1 text-xs font-medium text-muted-foreground">
                        Grain: {{ props.context.grain ?? 'Not selected' }}
                    </span>
                </div>
            </CardHeader>

            <CardContent class="space-y-5 px-5">
                <div class="grid gap-3 md:grid-cols-4">
                    <article
                        v-for="step in investigationSteps"
                        :key="step.label"
                        class="rounded-xl border p-3"
                        :class="step.active ? 'border-emerald-500/20 bg-emerald-500/5' : 'bg-background/70'"
                    >
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ step.label }}</p>
                        <p class="mt-2 text-sm leading-6" :class="step.active ? 'text-foreground' : 'text-muted-foreground'">
                            {{ step.description }}
                        </p>
                    </article>
                </div>

                <div v-if="incompleteConsumptionCount > 0 || incompleteDemandCount > 0 || unknownConsumptionCount > 0 || unknownDemandCount > 0 || missingIntervalCount > 0" class="rounded-xl border bg-background/70 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Profile Evidence</p>
                    <div class="mt-2 flex flex-wrap gap-2 text-xs font-medium">
                        <span v-if="incompleteConsumptionCount > 0" class="rounded-full bg-amber-500/10 px-2.5 py-1 text-amber-700 dark:text-amber-300">
                            Partial consumption: {{ incompleteConsumptionCount }}
                        </span>
                        <span v-if="incompleteDemandCount > 0" class="rounded-full bg-amber-500/10 px-2.5 py-1 text-amber-700 dark:text-amber-300">
                            Partial demand: {{ incompleteDemandCount }}
                        </span>
                        <span v-if="unknownConsumptionCount > 0" class="rounded-full bg-amber-500/10 px-2.5 py-1 text-amber-700 dark:text-amber-300">
                            Consumption review: {{ unknownConsumptionCount }}
                        </span>
                        <span v-if="unknownDemandCount > 0" class="rounded-full bg-amber-500/10 px-2.5 py-1 text-amber-700 dark:text-amber-300">
                            Demand review: {{ unknownDemandCount }}
                        </span>
                        <span v-if="missingIntervalCount > 0" class="rounded-full bg-muted px-2.5 py-1 text-muted-foreground">
                            Missing intervals: {{ missingIntervalCount }}
                        </span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div v-if="hasAnySeries" class="grid gap-5 xl:grid-cols-2">
            <ConsumptionTrend
                v-if="hasConsumption"
                :points="props.consumptionPoints"
                title="Consumption Shape"
                description="Consumption series for the selected load profile context."
            />
            <DemandCurve
                v-if="hasDemand"
                :points="props.demandPoints"
                title="Demand Shape"
                description="Demand series and peak markers for the selected load profile context."
            />
        </div>

        <Card v-else class="border-dashed py-8">
            <CardContent class="px-5 text-center">
                <p class="text-sm font-medium text-foreground">{{ props.emptyLabel }}</p>
                <p class="mt-2 text-sm leading-6 text-muted-foreground">
                    The explorer is ready for contract-backed series data. It does not use fake Vue values.
                </p>
            </CardContent>
        </Card>
    </section>
</template>
