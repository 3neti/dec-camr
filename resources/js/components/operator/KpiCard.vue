<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        title: string;
        value: string | number;
        description?: string;
        eyebrow?: string;
        trendLabel?: string;
        caption?: string;
        tone?: 'neutral' | 'success' | 'warning' | 'danger';
        class?: HTMLAttributes['class'];
    }>(),
    {
        description: '',
        eyebrow: '',
        trendLabel: '',
        caption: '',
        tone: 'neutral',
        class: undefined,
    },
);

const toneClasses = {
    neutral: 'border-border/70 bg-card',
    success: 'border-emerald-500/20 bg-emerald-500/5',
    warning: 'border-amber-500/20 bg-amber-500/5',
    danger: 'border-rose-500/20 bg-rose-500/5',
};

const trendToneClasses = {
    neutral: 'bg-muted text-muted-foreground',
    success: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    warning: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    danger: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
};
</script>

<template>
    <Card :class="cn('gap-4 overflow-hidden py-5', toneClasses[props.tone], props.class)">
        <CardHeader class="gap-3 px-5 pb-0">
            <div class="flex items-start justify-between gap-3">
                <div class="space-y-1">
                    <p
                        v-if="props.eyebrow"
                        class="text-[11px] font-medium uppercase tracking-[0.22em] text-muted-foreground/80"
                    >
                        {{ props.eyebrow }}
                    </p>
                    <p class="text-sm font-medium text-muted-foreground">
                        {{ props.title }}
                    </p>
                    <p v-if="props.description" class="text-sm leading-6 text-muted-foreground">
                        {{ props.description }}
                    </p>
                </div>

                <span
                    v-if="props.trendLabel"
                    class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-medium tracking-wide"
                    :class="trendToneClasses[props.tone]"
                >
                    {{ props.trendLabel }}
                </span>
            </div>
        </CardHeader>

        <CardContent class="flex items-end justify-between gap-4 px-5">
            <div class="space-y-1">
                <p class="text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                    {{ props.value }}
                </p>
                <p v-if="props.caption" class="text-sm text-muted-foreground">
                    {{ props.caption }}
                </p>
            </div>

            <slot name="aside" />
        </CardContent>
    </Card>
</template>
