<script setup lang="ts">
import AnalyticsEmptyState from '@/components/analytics/AnalyticsEmptyState.vue';
import OperatorPage from '@/components/operator/OperatorPage.vue';
import ScopePill from '@/components/operator/ScopePill.vue';
import StatusChip from '@/components/operator/StatusChip.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

type WorkbenchSection = {
    id: string;
    title: string;
    description: string;
    contract: string;
};

const props = defineProps<{
    title: string;
    subtitle: string;
    status: {
        label: string;
        description: string;
    };
    workbenchSections: WorkbenchSection[];
    readinessChecklist: string[];
}>();
</script>

<template>
    <OperatorPage
        :title="props.title"
        heading="Analytics Workbench"
        :description="props.subtitle"
    >
        <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_24rem]" aria-labelledby="analytics-shell-status">
            <Card class="overflow-hidden py-5">
                <CardHeader class="gap-3 px-5 pb-0">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div class="space-y-1">
                            <p class="text-[11px] font-medium uppercase tracking-[0.22em] text-muted-foreground/80">
                                Historical Analysis
                            </p>
                            <CardTitle id="analytics-shell-status">
                                {{ props.status.label }}
                            </CardTitle>
                            <CardDescription class="max-w-3xl">
                                {{ props.status.description }}
                            </CardDescription>
                        </div>

                        <StatusChip label="AN-016" tone="success" />
                    </div>
                </CardHeader>

                <CardContent class="space-y-5 px-5">
                    <div class="grid gap-3 md:grid-cols-2">
                        <article
                            v-for="section in props.workbenchSections"
                            :key="section.id"
                            class="rounded-xl border bg-background/70 p-4"
                        >
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="space-y-2">
                                    <h2 class="text-sm font-semibold text-foreground">
                                        {{ section.title }}
                                    </h2>
                                    <p class="text-sm leading-6 text-muted-foreground">
                                        {{ section.description }}
                                    </p>
                                </div>

                                <ScopePill label="Contract" :value="section.contract" tone="info" />
                            </div>
                        </article>
                    </div>

                    <AnalyticsEmptyState
                        kind="missing-filter"
                        title="Analytics data wiring is next"
                        description="This page is mounted intentionally before contract data is wired. AN-017 will connect the approved analytics contracts to this shell."
                        context-label="No scope selected"
                        source-label="AN-016 Route / Page Shell"
                        :recommended-actions="[
                            {
                                label: 'Prepare showcase data',
                                description: 'Run php artisan camr:scenario analytics-demo before visual review.',
                            },
                            {
                                label: 'Preserve Reports',
                                description: 'Use formal report pages for workbook exports until Analytics export workflows are approved.',
                            },
                        ]"
                    />
                </CardContent>
            </Card>

            <Card class="h-fit py-5">
                <CardHeader class="px-5 pb-0">
                    <CardTitle>Readiness Checklist</CardTitle>
                    <CardDescription>
                        What must be true before this becomes an analytical workspace.
                    </CardDescription>
                </CardHeader>

                <CardContent class="px-5">
                    <ol class="space-y-3">
                        <li
                            v-for="(item, index) in props.readinessChecklist"
                            :key="item"
                            class="flex gap-3 rounded-xl border bg-background/70 p-3 text-sm leading-6"
                        >
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-xs font-semibold text-white">
                                {{ index + 1 }}
                            </span>
                            <span class="text-muted-foreground">{{ item }}</span>
                        </li>
                    </ol>
                </CardContent>
            </Card>
        </section>
    </OperatorPage>
</template>
