<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        label: string;
        tone?: 'neutral' | 'success' | 'warning' | 'danger';
    }>(),
    {
        tone: 'neutral',
    },
);

const toneClasses = {
    neutral: 'bg-muted text-muted-foreground border-muted-foreground/20',
    success: 'bg-green-100 text-green-800 border-green-300 dark:bg-green-900/20 dark:text-green-300 dark:border-green-500/20',
    warning: 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-500/20',
    danger: 'bg-red-100 text-red-800 border-red-300 dark:bg-red-900/20 dark:text-red-300 dark:border-red-500/20',
};
</script>

<template>
    <span
        class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium"
        :class="toneClasses[props.tone]"
    >
        {{ props.label }}
    </span>
</template>
