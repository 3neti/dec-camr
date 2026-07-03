<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent, CardHeader } from '@/components/ui/card';

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

type TrendPoint = ConsumptionSeriesPoint & {
    index: number;
    x: number;
    y: number | null;
    formattedValue: string;
    label: string;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        points: ConsumptionSeriesPoint[];
        unit?: string;
        height?: number;
        emptyLabel?: string;
        sourceLabel?: string;
    }>(),
    {
        title: 'Consumption Trend',
        description: 'Report-compatible consumption over the selected analytical window.',
        unit: 'kWh',
        height: 220,
        emptyLabel: 'No consumption series points are available for this selection.',
        sourceLabel: 'ConsumptionSeriesPoint',
    },
);

const chartWidth = 640;
const chartPadding = 28;
const usableWidth = chartWidth - chartPadding * 2;
const usableHeight = computed(() => Math.max(120, props.height - chartPadding * 2));

const calculatedPoints = computed(() => props.points.filter((point) => typeof point.kwhTotal === 'number' && point.confidence.level === 'Calculated'));
const incompleteCount = computed(() => props.points.filter((point) => point.confidence.level === 'Incomplete').length);
const unknownCount = computed(() => props.points.filter((point) => point.confidence.level === 'Unknown').length);
const missingIntervalCount = computed(() => props.points.reduce((total, point) => total + (point.missingData?.missingIntervalCount ?? 0), 0));
const hasRenderableData = computed(() => calculatedPoints.value.length > 0);

const maxValue = computed(() => Math.max(...calculatedPoints.value.map((point) => point.kwhTotal ?? 0), 1));
const minValue = computed(() => Math.min(...calculatedPoints.value.map((point) => point.kwhTotal ?? 0), 0));
const valueRange = computed(() => Math.max(maxValue.value - minValue.value, 1));

const formatNumber = (value: number) => new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(value);
const formatLabel = (value: string) => new Intl.DateTimeFormat(undefined, {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
}).format(new Date(value));

const trendPoints = computed<TrendPoint[]>(() => props.points.map((point, index) => {
    const x = props.points.length > 1
        ? chartPadding + (index / (props.points.length - 1)) * usableWidth
        : chartWidth / 2;
    const y = typeof point.kwhTotal === 'number' && point.confidence.level === 'Calculated'
        ? chartPadding + ((maxValue.value - point.kwhTotal) / valueRange.value) * usableHeight.value
        : null;

    return {
        ...point,
        index,
        x,
        y,
        formattedValue: typeof point.kwhTotal === 'number' ? `${formatNumber(point.kwhTotal)} ${props.unit}` : 'Unavailable',
        label: formatLabel(point.periodStart),
    };
}));

const linePath = computed(() => trendPoints.value
    .filter((point) => point.y !== null)
    .map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x.toFixed(2)} ${(point.y ?? 0).toFixed(2)}`)
    .join(' '));

const areaPath = computed(() => {
    const renderablePoints = trendPoints.value.filter((point) => point.y !== null);

    if (renderablePoints.length === 0) {
        return '';
    }

    const baseline = chartPadding + usableHeight.value;
    const line = renderablePoints
        .map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x.toFixed(2)} ${(point.y ?? 0).toFixed(2)}`)
        .join(' ');
    const first = renderablePoints[0];
    const last = renderablePoints[renderablePoints.length - 1];

    return `${line} L ${last.x.toFixed(2)} ${baseline.toFixed(2)} L ${first.x.toFixed(2)} ${baseline.toFixed(2)} Z`;
});

const totalKwh = computed(() => calculatedPoints.value.reduce((total, point) => total + (point.kwhTotal ?? 0), 0));
const averageKwh = computed(() => calculatedPoints.value.length > 0 ? totalKwh.value / calculatedPoints.value.length : null);
const peakPoint = computed(() => calculatedPoints.value.reduce<ConsumptionSeriesPoint | null>((peak, point) => {
    if (peak === null || (point.kwhTotal ?? 0) > (peak.kwhTotal ?? 0)) {
        return point;
    }

    return peak;
}, null));

const confidenceLabel = computed(() => {
    if (props.points.length === 0) {
        return 'No Data';
    }

    if (incompleteCount.value > 0) {
        return 'Incomplete';
    }

    if (unknownCount.value > 0) {
        return 'Review Needed';
    }

    return 'Calculated';
});

const confidenceClasses = computed(() => {
    if (confidenceLabel.value === 'Calculated') {
        return 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    if (confidenceLabel.value === 'Review Needed') {
        return 'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-300';
    }

    if (confidenceLabel.value === 'Incomplete') {
        return 'border-rose-500/20 bg-rose-500/10 text-rose-700 dark:text-rose-300';
    }

    return 'border-border bg-muted text-muted-foreground';
});
</script>

<template>
    <Card class="gap-4 overflow-hidden py-5">
        <CardHeader class="gap-3 px-5 pb-0">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-1">
                    <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-muted-foreground/80">
                        Trend
                    </p>
                    <h3 class="text-base font-semibold text-foreground">
                        {{ props.title }}
                    </h3>
                    <p class="max-w-2xl text-sm leading-6 text-muted-foreground">
                        {{ props.description }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex w-fit items-center rounded-full border px-2.5 py-1 text-xs font-medium" :class="confidenceClasses">
                        {{ confidenceLabel }}
                    </span>
                    <span class="inline-flex w-fit items-center rounded-full border bg-muted/60 px-2.5 py-1 text-xs font-medium text-muted-foreground">
                        Source: {{ props.sourceLabel }}
                    </span>
                </div>
            </div>
        </CardHeader>

        <CardContent class="space-y-5 px-5">
            <div class="grid gap-3 md:grid-cols-3">
                <div class="rounded-xl border bg-background/70 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total</p>
                    <p class="mt-1 text-xl font-semibold text-foreground">{{ formatNumber(totalKwh) }} {{ props.unit }}</p>
                </div>
                <div class="rounded-xl border bg-background/70 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Average</p>
                    <p class="mt-1 text-xl font-semibold text-foreground">
                        {{ averageKwh === null ? 'Unavailable' : `${formatNumber(averageKwh)} ${props.unit}` }}
                    </p>
                </div>
                <div class="rounded-xl border bg-background/70 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Peak Window</p>
                    <p class="mt-1 text-xl font-semibold text-foreground">
                        {{ peakPoint?.kwhTotal === undefined || peakPoint?.kwhTotal === null ? 'Unavailable' : `${formatNumber(peakPoint.kwhTotal)} ${props.unit}` }}
                    </p>
                </div>
            </div>

            <div v-if="hasRenderableData" class="rounded-2xl border bg-background/70 p-4">
                <svg
                    class="h-auto w-full overflow-visible"
                    :viewBox="`0 0 ${chartWidth} ${props.height}`"
                    role="img"
                    :aria-label="`${props.title}: ${trendPoints.length} consumption points`"
                >
                    <line
                        :x1="chartPadding"
                        :x2="chartWidth - chartPadding"
                        :y1="chartPadding + usableHeight"
                        :y2="chartPadding + usableHeight"
                        class="stroke-muted"
                        stroke-width="1"
                    />
                    <path v-if="areaPath" :d="areaPath" class="fill-emerald-500/10" />
                    <path v-if="linePath" :d="linePath" class="fill-none stroke-emerald-600 dark:stroke-emerald-400" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />

                    <g v-for="point in trendPoints" :key="`${point.periodStart}-${point.index}`">
                        <circle
                            v-if="point.y !== null"
                            :cx="point.x"
                            :cy="point.y"
                            r="4"
                            class="fill-background stroke-emerald-600 dark:stroke-emerald-400"
                            stroke-width="2"
                        />
                        <circle
                            v-else
                            :cx="point.x"
                            :cy="chartPadding + usableHeight"
                            r="3"
                            class="fill-amber-500"
                        />
                        <title>{{ point.label }}: {{ point.formattedValue }} ({{ point.confidence.level }})</title>
                    </g>
                </svg>

                <div class="mt-3 flex flex-wrap justify-between gap-3 text-xs text-muted-foreground">
                    <span>{{ trendPoints[0]?.label }}</span>
                    <span>{{ trendPoints[trendPoints.length - 1]?.label }}</span>
                </div>
            </div>

            <div v-else class="rounded-2xl border border-dashed bg-muted/30 p-6 text-center">
                <p class="text-sm font-medium text-foreground">{{ props.emptyLabel }}</p>
                <p class="mt-2 text-sm leading-6 text-muted-foreground">
                    Choose a valid time range and aggregation, or review missing/incomplete source data.
                </p>
            </div>

            <div v-if="incompleteCount > 0 || unknownCount > 0 || missingIntervalCount > 0" class="rounded-xl border bg-background/70 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Data Quality</p>
                <div class="mt-2 flex flex-wrap gap-2 text-xs font-medium">
                    <span v-if="incompleteCount > 0" class="rounded-full bg-rose-500/10 px-2.5 py-1 text-rose-700 dark:text-rose-300">
                        Incomplete points: {{ incompleteCount }}
                    </span>
                    <span v-if="unknownCount > 0" class="rounded-full bg-amber-500/10 px-2.5 py-1 text-amber-700 dark:text-amber-300">
                        Unknown points: {{ unknownCount }}
                    </span>
                    <span v-if="missingIntervalCount > 0" class="rounded-full bg-muted px-2.5 py-1 text-muted-foreground">
                        Missing intervals: {{ missingIntervalCount }}
                    </span>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
