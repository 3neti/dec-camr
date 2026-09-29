<script setup lang="ts">
import type { EChartsOption } from 'echarts';
import { computed } from 'vue';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import BaseAnalyticsChart from './BaseAnalyticsChart.vue';
import { baseGrid, baseTooltip, categoryAxis, formatChartLabel, formatChartNumber, valueAxis } from './charting';

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
        height: 280,
        emptyLabel: 'No consumption series points are available for this selection.',
        sourceLabel: 'ConsumptionSeriesPoint',
    },
);

const calculatedPoints = computed(() => props.points.filter((point) => typeof point.kwhTotal === 'number' && point.confidence.level === 'Calculated'));
const incompleteCount = computed(() => props.points.filter((point) => point.confidence.level === 'Incomplete').length);
const unknownCount = computed(() => props.points.filter((point) => point.confidence.level === 'Unknown').length);
const missingIntervalCount = computed(() => props.points.reduce((total, point) => total + (point.missingData?.missingIntervalCount ?? 0), 0));
const hasRenderableData = computed(() => calculatedPoints.value.length > 0);

const chartLabels = computed(() => props.points.map((point) => formatChartLabel(point.periodStart)));
const chartValues = computed(() => props.points.map((point) => point.confidence.level === 'Calculated' ? point.kwhTotal : null));

const chartOption = computed<EChartsOption>(() => ({
    color: ['#059669'],
    grid: baseGrid,
    tooltip: {
        ...baseTooltip,
        valueFormatter: (value: unknown) => `${formatChartNumber(Number(value))} ${props.unit}`,
    },
    xAxis: categoryAxis(chartLabels.value),
    yAxis: valueAxis(props.unit),
    series: [
        {
            name: 'Consumption',
            type: 'line',
            data: chartValues.value,
            smooth: true,
            connectNulls: false,
            showSymbol: true,
            symbolSize: 7,
            lineStyle: {
                width: 3,
            },
            areaStyle: {
                color: 'rgba(5, 150, 105, 0.14)',
            },
            emphasis: {
                focus: 'series',
            },
        },
    ],
}));

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
        return 'Partial evidence';
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

    if (confidenceLabel.value === 'Partial evidence') {
        return 'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-300';
    }

    return 'border-border bg-muted text-muted-foreground';
});
</script>

<template>
    <Card class="gap-4 overflow-hidden py-5" data-test="analytics-consumption-trend">
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
                        Source: Consumption
                    </span>
                </div>
            </div>
        </CardHeader>

        <CardContent class="space-y-5 px-5">
            <div class="grid gap-3 md:grid-cols-3">
                <div class="rounded-xl border bg-background/70 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total</p>
                    <p class="mt-1 text-xl font-semibold text-foreground">{{ formatChartNumber(totalKwh) }} {{ props.unit }}</p>
                </div>
                <div class="rounded-xl border bg-background/70 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Average</p>
                    <p class="mt-1 text-xl font-semibold text-foreground">
                        {{ averageKwh === null ? 'Unavailable' : `${formatChartNumber(averageKwh)} ${props.unit}` }}
                    </p>
                </div>
                <div class="rounded-xl border bg-background/70 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Peak Window</p>
                    <p class="mt-1 text-xl font-semibold text-foreground">
                        {{ peakPoint?.kwhTotal === undefined || peakPoint?.kwhTotal === null ? 'Unavailable' : `${formatChartNumber(peakPoint.kwhTotal)} ${props.unit}` }}
                    </p>
                </div>
            </div>

            <BaseAnalyticsChart
                v-if="hasRenderableData"
                test-id="analytics-consumption-chart"
                :option="chartOption"
                :height="props.height"
                :ariaLabel="`${props.title}: ${calculatedPoints.length} calculated consumption points`"
            />

            <div v-else class="rounded-2xl border border-dashed bg-muted/30 p-6 text-center">
                <p class="text-sm font-medium text-foreground">{{ props.emptyLabel }}</p>
                <p class="mt-2 text-sm leading-6 text-muted-foreground">
                    Choose a valid time range and aggregation, or review missing/incomplete source data.
                </p>
            </div>

            <div v-if="incompleteCount > 0 || unknownCount > 0 || missingIntervalCount > 0" class="rounded-xl border bg-background/70 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Evidence Quality</p>
                <div class="mt-2 flex flex-wrap gap-2 text-xs font-medium">
                    <span v-if="incompleteCount > 0" class="rounded-full bg-amber-500/10 px-2.5 py-1 text-amber-700 dark:text-amber-300">
                        Partial evidence: {{ incompleteCount }}
                    </span>
                    <span v-if="unknownCount > 0" class="rounded-full bg-amber-500/10 px-2.5 py-1 text-amber-700 dark:text-amber-300">
                        Review needed: {{ unknownCount }}
                    </span>
                    <span v-if="missingIntervalCount > 0" class="rounded-full bg-muted px-2.5 py-1 text-muted-foreground">
                        Missing intervals: {{ missingIntervalCount }}
                    </span>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
