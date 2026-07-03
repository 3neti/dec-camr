<script setup lang="ts">
import { AlertTriangle, Database, Filter, SearchX } from '@lucide/vue';
import { computed } from 'vue';
import { Card, CardContent } from '@/components/ui/card';

type EmptyStateKind = 'missing-filter' | 'no-data' | 'incomplete-data' | 'unsupported-grain';

type RecommendedAction = {
    label: string;
    description?: string;
};

const props = withDefaults(
    defineProps<{
        kind: EmptyStateKind;
        title?: string;
        description?: string;
        contextLabel?: string;
        sourceLabel?: string;
        missingIntervalCount?: number;
        unsupportedGrain?: string;
        recommendedActions?: RecommendedAction[];
        compact?: boolean;
    }>(),
    {
        title: '',
        description: '',
        contextLabel: '',
        sourceLabel: 'Analytics contract',
        missingIntervalCount: 0,
        unsupportedGrain: '',
        recommendedActions: () => [],
        compact: false,
    },
);

const stateDefaults = computed(() => ({
    'missing-filter': {
        eyebrow: 'Filter Required',
        title: 'Choose an analytical scope',
        description: 'Select a meter, building, time range, and grain before requesting analytics evidence.',
        icon: Filter,
        tone: 'info',
    },
    'no-data': {
        eyebrow: 'No Data',
        title: 'No telemetry matches this selection',
        description: 'The selected scope is valid, but no contract-backed analytics rows were found for the requested window.',
        icon: SearchX,
        tone: 'neutral',
    },
    'incomplete-data': {
        eyebrow: 'Incomplete Evidence',
        title: 'Analytics evidence is incomplete',
        description: 'One or more required boundary readings are missing, so the result should be reviewed before drawing conclusions.',
        icon: AlertTriangle,
        tone: 'danger',
    },
    'unsupported-grain': {
        eyebrow: 'Unsupported Grain',
        title: 'This aggregation is not available yet',
        description: 'The selected grain is not supported by the current analytics contract for this workspace.',
        icon: Database,
        tone: 'warning',
    },
}[props.kind]));

const displayTitle = computed(() => props.title || stateDefaults.value.title);
const displayDescription = computed(() => props.description || stateDefaults.value.description);
const displayEyebrow = computed(() => stateDefaults.value.eyebrow);
const IconComponent = computed(() => stateDefaults.value.icon);

const toneClasses = computed(() => {
    if (stateDefaults.value.tone === 'danger') {
        return {
            card: 'border-rose-500/20 bg-rose-500/5',
            icon: 'border-rose-500/20 bg-rose-500/10 text-rose-700 dark:text-rose-300',
            badge: 'border-rose-500/20 bg-rose-500/10 text-rose-700 dark:text-rose-300',
        };
    }

    if (stateDefaults.value.tone === 'warning') {
        return {
            card: 'border-amber-500/20 bg-amber-500/5',
            icon: 'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-300',
            badge: 'border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-300',
        };
    }

    if (stateDefaults.value.tone === 'info') {
        return {
            card: 'border-sky-500/20 bg-sky-500/5',
            icon: 'border-sky-500/20 bg-sky-500/10 text-sky-700 dark:text-sky-300',
            badge: 'border-sky-500/20 bg-sky-500/10 text-sky-700 dark:text-sky-300',
        };
    }

    return {
        card: 'border-dashed bg-card',
        icon: 'border-border bg-muted text-muted-foreground',
        badge: 'border-border bg-muted text-muted-foreground',
    };
});
</script>

<template>
    <Card :class="['overflow-hidden', compact ? 'py-4' : 'py-6', toneClasses.card]">
        <CardContent :class="['space-y-5', compact ? 'px-4' : 'px-6']">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                <div :class="['flex size-11 shrink-0 items-center justify-center rounded-2xl border', toneClasses.icon]" aria-hidden="true">
                    <component :is="IconComponent" class="size-5" />
                </div>

                <div class="min-w-0 flex-1 space-y-3">
                    <div class="space-y-1">
                        <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-muted-foreground/80">
                            {{ displayEyebrow }}
                        </p>
                        <h3 class="text-base font-semibold text-foreground">
                            {{ displayTitle }}
                        </h3>
                        <p class="max-w-2xl text-sm leading-6 text-muted-foreground">
                            {{ displayDescription }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span v-if="contextLabel" class="inline-flex w-fit items-center rounded-full border bg-background/70 px-2.5 py-1 text-xs font-medium text-muted-foreground">
                            Context: {{ contextLabel }}
                        </span>
                        <span class="inline-flex w-fit items-center rounded-full border bg-background/70 px-2.5 py-1 text-xs font-medium text-muted-foreground">
                            Source: {{ sourceLabel }}
                        </span>
                        <span v-if="kind === 'incomplete-data'" class="inline-flex w-fit items-center rounded-full border px-2.5 py-1 text-xs font-medium" :class="toneClasses.badge">
                            Missing intervals: {{ missingIntervalCount }}
                        </span>
                        <span v-if="kind === 'unsupported-grain' && unsupportedGrain" class="inline-flex w-fit items-center rounded-full border px-2.5 py-1 text-xs font-medium" :class="toneClasses.badge">
                            Grain: {{ unsupportedGrain }}
                        </span>
                    </div>
                </div>
            </div>

            <div v-if="recommendedActions.length > 0" class="grid gap-3 md:grid-cols-2">
                <article
                    v-for="action in recommendedActions"
                    :key="action.label"
                    class="rounded-xl border bg-background/70 p-3"
                >
                    <p class="text-sm font-semibold text-foreground">
                        {{ action.label }}
                    </p>
                    <p v-if="action.description" class="mt-1 text-sm leading-6 text-muted-foreground">
                        {{ action.description }}
                    </p>
                </article>
            </div>

            <slot />
        </CardContent>
    </Card>
</template>
