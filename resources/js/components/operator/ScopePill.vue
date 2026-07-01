<script setup lang="ts">
import { computed } from 'vue';

type ScopeTone = 'neutral' | 'info' | 'warning' | 'success';

const props = withDefaults(
    defineProps<{
        label?: string;
        value: string;
        tone?: ScopeTone;
    }>(),
    {
        label: 'Scope',
        tone: 'neutral',
    },
);

const toneClass = computed(() => {
    return {
        neutral: 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-800 dark:bg-slate-900/70 dark:text-slate-200',
        info: 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900/80 dark:bg-sky-950/70 dark:text-sky-200',
        warning: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/80 dark:bg-amber-950/70 dark:text-amber-200',
        success: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/80 dark:bg-emerald-950/70 dark:text-emerald-200',
    }[props.tone];
});
</script>

<template>
    <div
        class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-medium"
        :class="toneClass"
    >
        <span class="uppercase tracking-[0.18em] opacity-70">{{ props.label }}</span>
        <span class="font-semibold tracking-normal">{{ props.value }}</span>
    </div>
</template>
