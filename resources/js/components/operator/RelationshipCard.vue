<script setup lang="ts">
const props = defineProps<{
    eyebrow?: string;
    title: string;
    description?: string;
    meta?: string[];
}>();
</script>

<template>
    <article class="group flex h-full flex-col justify-between rounded-3xl border border-border/70 bg-card p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md dark:hover:border-emerald-700">
        <div class="space-y-4">
            <div class="space-y-2">
                <p v-if="props.eyebrow" class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-700 dark:text-emerald-300">
                    {{ props.eyebrow }}
                </p>
                <h3 class="text-xl font-semibold tracking-tight text-foreground">
                    {{ props.title }}
                </h3>
                <p v-if="props.description" class="text-sm leading-6 text-muted-foreground">
                    {{ props.description }}
                </p>
            </div>
            <div v-if="props.meta?.length" class="flex flex-wrap gap-2">
                <span v-for="item in props.meta" :key="item" class="rounded-full border border-border bg-muted/50 px-3 py-1 text-xs font-medium text-muted-foreground">
                    {{ item }}
                </span>
            </div>
        </div>
        <div v-if="$slots.default" class="mt-5 border-t border-border/70 pt-4">
            <slot />
        </div>
    </article>
</template>
