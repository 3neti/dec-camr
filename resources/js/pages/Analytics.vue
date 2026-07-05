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

type AnalyticsContext = {
    hasData: boolean;
    meterIdentifier: string | null;
    buildingCode: string | null;
    siteCode: string | null;
    from: string | null;
    to: string | null;
    periodLabel: string;
    grain: string;
};

type ContractEvidence = {
    consumptionPointCount: number;
    demandPointCount: number;
    buildingSummaryCount: number;
    calculatedConsumptionCount: number;
    calculatedDemandCount: number;
    incompleteCount: number;
    unknownCount: number;
    topBuildingCode: string | null;
};

type EmptyStateKind = 'missing-filter' | 'no-data' | 'incomplete-data' | 'unsupported-grain';

const props = defineProps<{
    title: string;
    subtitle: string;
    status: {
        label: string;
        description: string;
    };
    workbenchSections: WorkbenchSection[];
    readinessChecklist: string[];
    analyticsContext: AnalyticsContext;
    contractEvidence: ContractEvidence;
    contractData: {
        consumptionPoints: unknown[];
        demandPoints: unknown[];
        buildingSummaries: unknown[];
    };
    emptyState: {
        kind: EmptyStateKind;
        title: string;
        description: string;
        contextLabel: string;
        sourceLabel: string;
        missingIntervalCount: number;
    };
}>();

const evidenceCards = [
    {
        label: 'Consumption Points',
        value: props.contractEvidence.consumptionPointCount,
        detail: `${props.contractEvidence.calculatedConsumptionCount} calculated`,
    },
    {
        label: 'Demand Points',
        value: props.contractEvidence.demandPointCount,
        detail: `${props.contractEvidence.calculatedDemandCount} calculated`,
    },
    {
        label: 'Building Summaries',
        value: props.contractEvidence.buildingSummaryCount,
        detail: props.contractEvidence.topBuildingCode ? `Top: ${props.contractEvidence.topBuildingCode}` : 'No top building',
    },
    {
        label: 'Review Flags',
        value: props.contractEvidence.incompleteCount + props.contractEvidence.unknownCount,
        detail: `${props.contractEvidence.incompleteCount} incomplete / ${props.contractEvidence.unknownCount} unknown`,
    },
];
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

                        <StatusChip label="AN-017" tone="success" />
                    </div>
                </CardHeader>

                <CardContent class="space-y-5 px-5">
                    <div class="flex flex-wrap gap-2">
                        <ScopePill
                            label="Building"
                            :value="props.analyticsContext.buildingCode ?? 'No data'"
                            :tone="props.analyticsContext.hasData ? 'success' : 'warning'"
                        />
                        <ScopePill
                            label="Meter"
                            :value="props.analyticsContext.meterIdentifier ?? 'No meter'"
                            :tone="props.analyticsContext.hasData ? 'info' : 'warning'"
                        />
                        <ScopePill
                            label="Grain"
                            :value="props.analyticsContext.grain"
                            tone="neutral"
                        />
                    </div>

                    <dl class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <div
                            v-for="card in evidenceCards"
                            :key="card.label"
                            class="rounded-xl border bg-background/70 p-4"
                        >
                            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                {{ card.label }}
                            </dt>
                            <dd class="mt-2 text-2xl font-semibold text-foreground">
                                {{ card.value }}
                            </dd>
                            <dd class="mt-1 text-sm leading-6 text-muted-foreground">
                                {{ card.detail }}
                            </dd>
                        </div>
                    </dl>

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
                        :kind="props.emptyState.kind"
                        :title="props.emptyState.title"
                        :description="props.emptyState.description"
                        :context-label="props.emptyState.contextLabel"
                        :source-label="props.emptyState.sourceLabel"
                        :missing-interval-count="props.emptyState.missingIntervalCount"
                        :recommended-actions="[
                            {
                                label: props.analyticsContext.hasData ? 'Compose workspace' : 'Prepare showcase data',
                                description: props.analyticsContext.hasData
                                    ? 'AN-018 can now mount summary cards, trend, demand, comparison, and load-profile components.'
                                    : 'Run php artisan camr:scenario analytics-demo before visual review.',
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
