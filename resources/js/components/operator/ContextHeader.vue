<script setup lang="ts">
const props = defineProps<{
    eyebrow?: string;
    title: string;
    description?: string;
    meta?: string[];
}>();
</script>

<template>
    <section class="overflow-hidden rounded-3xl border border-border/70 bg-card shadow-sm">
        <div class="bg-gradient-to-br from-emerald-50 via-white to-slate-50 px-5 py-6 sm:px-7 dark:from-emerald-950/20 dark:via-card dark:to-slate-950/20">
            <p v-if="props.eyebrow" class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-700 dark:text-emerald-300">
                {{ props.eyebrow }}
            </p>
            <div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl space-y-2">
                    <h2 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">
                        {{ props.title }}
                    </h2>
                    <p v-if="props.description" class="text-sm leading-6 text-muted-foreground sm:text-base">
                        {{ props.description }}
                    </p>
                </div>
                <dl v-if="props.meta?.length" class="grid gap-2 sm:grid-cols-2 lg:min-w-80">
                    <div v-for="item in props.meta" :key="item" class="rounded-2xl border border-white/70 bg-white/75 px-4 py-3 text-sm font-medium text-slate-700 shadow-sm dark:border-white/10 dark:bg-white/5 dark:text-slate-200">
                        {{ item }}
                    </div>
                </dl>
            </div>
        </div>
    </section>
</template>
