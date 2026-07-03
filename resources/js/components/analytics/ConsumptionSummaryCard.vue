<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent, CardHeader } from '@/components/ui/card';

type TrustLevel = 'Measured' | 'Calculated' | 'Estimated' | 'Incomplete' | 'Unknown';

type DataTrustIndicator = {
    level: TrustLevel;
    reason?: string;
    missingIntervalCount?: number;
    warning?: string | null;
};

type ConsumptionComparison = {
    label: string;
    value?: string | number | null;
    direction?: 'up' | 'down' | 'flat' | 'none';
    context?: string;
};

const props = withDefaults(
    defineProps<{
        title: string;
        value: string | number | null;
        unit?: string;
        periodLabel: string;
        comparison?: ConsumptionComparison | null;
        confidence: DataTrustIndicator;
        caption?: string;
        sourceLabel?: string;
    }>(),
    {
        unit: 'kWh',
        comparison: null,
        caption: '',
        sourceLabel: 'ConsumptionSeriesPoint',
    },
);

const formattedValue = computed(() => {
    if (props.value === null || props.value === undefined || props.value === '') {
        return 'Unavailable';
    }

    if (typeof props.value === 'number') {
        return new Intl.NumberFormat(undefined, {
            maximumFractionDigits: 2,
        }).format(props.value);
    }

    return props.value;
});

const confidenceTone = computed<'success' | 'warning' | 'danger' | 'neutral'>(() => {
    if (props.confidence.level === 'Measured' || props.confidence.level === 'Calculated') {
        return 'success';
    }

    if (props.confidence.level === 'Estimated' || props.confidence.level === 'Unknown') {
        return 'warning';
    }

    if (props.confidence.level === 'Incomplete') {
        return 'danger';
    }

    return 'neutral';
});

const confidenceClasses = computed(() => ({
    success: 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    warning: 'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-300',
    danger: 'border-rose-500/20 bg-rose-500/10 text-rose-700 dark:text-rose-300',
    neutral: 'border-border bg-muted text-muted-foreground',
}[confidenceTone.value]));

const cardToneClasses = computed(() => ({
    success: 'border-emerald-500/20 bg-emerald-500/5',
    warning: 'border-amber-500/20 bg-amber-500/5',
    danger: 'border-rose-500/20 bg-rose-500/5',
    neutral: 'border-border/70 bg-card',
}[confidenceTone.value]));

const comparisonClasses = computed(() => {
    if (props.comparison?.direction === 'up') {
        return 'bg-amber-500/10 text-amber-700 dark:text-amber-300';
    }

    if (props.comparison?.direction === 'down') {
        return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    return 'bg-muted text-muted-foreground';
});

const comparisonValue = computed(() => {
    if (!props.comparison || props.comparison.value === null || props.comparison.value === undefined || props.comparison.value === '') {
        return '';
    }

    return typeof props.comparison.value === 'number'
        ? new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(props.comparison.value)
        : String(props.comparison.value);
});
</script>

<template>
    <Card :class="['gap-4 overflow-hidden py-5', cardToneClasses]">
        <CardHeader class="gap-3 px-5 pb-0">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-1">
                    <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-muted-foreground/80">
                        Consumption Summary
                    </p>
                    <h3 class="text-base font-semibold text-foreground">
                        {{ props.title }}
                    </h3>
                    <p class="text-sm leading-6 text-muted-foreground">
                        {{ props.periodLabel }}
                    </p>
                </div>

                <span class="inline-flex w-fit items-center rounded-full border px-2.5 py-1 text-xs font-medium" :class="confidenceClasses">
                    {{ props.confidence.level }}
                </span>
            </div>
        </CardHeader>

        <CardContent class="space-y-5 px-5">
            <div class="flex flex-col gap-2">
                <div class="flex flex-wrap items-end gap-x-2 gap-y-1">
                    <p class="text-4xl font-semibold tracking-tight text-foreground">
                        {{ formattedValue }}
                    </p>
                    <p v-if="props.value !== null && props.value !== undefined && props.value !== ''" class="pb-1 text-sm font-medium text-muted-foreground">
                        {{ props.unit }}
                    </p>
                </div>

                <p v-if="props.caption" class="text-sm leading-6 text-muted-foreground">
                    {{ props.caption }}
                </p>
            </div>

            <div v-if="props.comparison" class="rounded-xl border bg-background/70 p-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="space-y-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            {{ props.comparison.label }}
                        </p>
                        <p v-if="props.comparison.context" class="text-sm text-muted-foreground">
                            {{ props.comparison.context }}
                        </p>
                    </div>

                    <span v-if="comparisonValue" class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="comparisonClasses">
                        {{ comparisonValue }}
                    </span>
                </div>
            </div>

            <div class="space-y-2 rounded-xl border bg-background/70 p-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        Trust
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Source: {{ props.sourceLabel }}
                    </p>
                </div>

                <p v-if="props.confidence.reason" class="text-sm leading-6 text-muted-foreground">
                    {{ props.confidence.reason }}
                </p>
                <p v-if="props.confidence.warning" class="text-sm font-medium text-amber-700 dark:text-amber-300">
                    {{ props.confidence.warning }}
                </p>
                <p v-if="props.confidence.missingIntervalCount" class="text-sm font-medium text-rose-700 dark:text-rose-300">
                    Missing intervals: {{ props.confidence.missingIntervalCount }}
                </p>
            </div>
        </CardContent>
    </Card>
</template>
