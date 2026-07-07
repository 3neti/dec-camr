<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AnalyticsController from '@/actions/App/Http/Controllers/AnalyticsController';
import {
    consumptionReport,
    demandReport,
    rawReport,
    siteReport,
} from '@/actions/App/Http/Controllers/ReportController';
import AnalyticsContextSelector from '@/components/analytics/AnalyticsContextSelector.vue';
import AnalyticsEmptyState from '@/components/analytics/AnalyticsEmptyState.vue';
import AnalyticsExportPanel from '@/components/analytics/AnalyticsExportPanel.vue';
import BuildingComparisonGrid from '@/components/analytics/BuildingComparisonGrid.vue';
import ConsumptionSummaryCard from '@/components/analytics/ConsumptionSummaryCard.vue';
import ConsumptionTrend from '@/components/analytics/ConsumptionTrend.vue';
import DemandCurve from '@/components/analytics/DemandCurve.vue';
import LoadProfileExplorer from '@/components/analytics/LoadProfileExplorer.vue';
import TimeRangePicker from '@/components/analytics/TimeRangePicker.vue';
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

type TimeRangePreset = {
    key: string;
    label: string;
    from: string;
    to: string;
    description?: string;
};

type TimeRangeControls = {
    from: string;
    to: string;
    min: string;
    max: string;
    timezoneLabel: string;
    presets: TimeRangePreset[];
};

type ContextControls = {
    buildingCode: string;
    meterIdentifier: string;
    buildingOptions: {
        value: string;
        label: string;
        description: string;
    }[];
    meterOptions: {
        value: string;
        label: string;
        description: string;
        buildingCode: string;
    }[];
};

type QueryState = {
    building: string | null;
    meter: string | null;
    from: string | null;
    to: string | null;
    url: string;
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
type TrustLevel = 'Measured' | 'Calculated' | 'Estimated' | 'Incomplete' | 'Unknown';
type ReportFamily = 'consumption' | 'demand' | 'raw' | 'site';

type DataTrustIndicator = {
    level: TrustLevel;
    reason?: string;
    missingIntervalCount?: number;
    warning?: string | null;
};

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

type DemandSeriesPoint = {
    periodStart: string;
    periodEnd: string;
    grain: string;
    kwDemand: number | null;
    elapsedMinutes?: number | null;
    peakMarker?: {
        isPeak?: boolean;
        peakKwDemand?: number | null;
    };
    confidence: {
        level: TrustLevel;
        reason?: string;
    };
    missingData?: {
        missingIntervalCount?: number;
        missingMinReading?: boolean;
        missingMaxReading?: boolean;
    };
};

type BuildingConsumptionSummary = {
    buildingId: number;
    buildingCode: string;
    buildingName: string;
    site?: {
        siteId?: number | null;
        siteCode?: string | null;
    };
    totalKwh: number;
    meterCount: number;
    seriesPointCount: number;
    missingData: {
        missingIntervalCount: number;
        incompleteSeriesPointCount: number;
        unknownSeriesPointCount: number;
    };
    comparison?: {
        rankBasis?: string;
        isTopConsumer?: boolean;
        topConsumerKwh?: number | null;
    };
    confidence: {
        level: TrustLevel;
        reason?: string;
    };
};

type ExportPanelAction = {
    id: string;
    label: string;
    description: string;
    reportFamily: ReportFamily;
};

const props = defineProps<{
    title: string;
    subtitle: string;
    status: {
        label: string;
        description: string;
    };
    exportPanel: {
        title: string;
        description: string;
        actions: ExportPanelAction[];
        preservationNote: string;
    };
    workbenchSections: WorkbenchSection[];
    readinessChecklist: string[];
    analyticsContext: AnalyticsContext;
    timeRangeControls: TimeRangeControls;
    contextControls: ContextControls;
    queryState: QueryState;
    contractEvidence: ContractEvidence;
    contractData: {
        consumptionPoints: ConsumptionSeriesPoint[];
        demandPoints: DemandSeriesPoint[];
        buildingSummaries: BuildingConsumptionSummary[];
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

const selectedFrom = ref(props.timeRangeControls.from);
const selectedTo = ref(props.timeRangeControls.to);

watch(
    () => props.timeRangeControls,
    (controls) => {
        selectedFrom.value = controls.from;
        selectedTo.value = controls.to;
    },
);

const selectedBuildingCode = ref(props.contextControls.buildingCode);
const selectedMeterIdentifier = ref(props.contextControls.meterIdentifier);

watch(
    () => props.contextControls,
    (controls) => {
        selectedBuildingCode.value = controls.buildingCode;
        selectedMeterIdentifier.value = controls.meterIdentifier;
    },
);

const evidenceCards = computed(() => [
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
        label: 'Quality Notes',
        value: props.contractEvidence.incompleteCount + props.contractEvidence.unknownCount,
        detail: `${props.contractEvidence.incompleteCount} partial / ${props.contractEvidence.unknownCount} review`,
    },
]);

const contractLabels: Record<string, { short: string; full: string }> = {
    ConsumptionSeriesPoint: { short: 'Consumption', full: 'ConsumptionSeriesPoint' },
    DemandSeriesPoint: { short: 'Demand', full: 'DemandSeriesPoint' },
    BuildingConsumptionSummary: { short: 'Building Summary', full: 'BuildingConsumptionSummary' },
    'ConsumptionSeriesPoint + DemandSeriesPoint': { short: 'Load Profile', full: 'ConsumptionSeriesPoint + DemandSeriesPoint' },
};

const displayContract = (contract: string) => contractLabels[contract] ?? { short: contract, full: contract };

const reportUrls: Record<ReportFamily, string> = {
    consumption: consumptionReport.url(),
    demand: demandReport.url(),
    raw: rawReport.url(),
    site: siteReport.url(),
};

const exportPanelActions = computed(() => props.exportPanel.actions.map((action) => ({
    id: action.id,
    label: action.label,
    description: action.description,
    href: reportUrls[action.reportFamily],
    primary: action.reportFamily === 'consumption',
})));

const visitAnalytics = (query: Partial<Omit<QueryState, 'url'>>) => {
    router.visit(AnalyticsController.url({
        query: {
            building: query.building ?? props.queryState.building ?? undefined,
            meter: query.meter ?? props.queryState.meter ?? undefined,
            from: query.from ?? props.queryState.from ?? undefined,
            to: query.to ?? props.queryState.to ?? undefined,
        },
    }), {
        method: 'get',
        preserveScroll: true,
        preserveState: false,
    });
};

const applyTimeRange = (payload: { from: string; to: string; timezoneLabel: string; valid: boolean }) => {
    if (!payload.valid) {
        return;
    }

    visitAnalytics({
        from: payload.from,
        to: payload.to,
    });
};

const applyContext = (payload: { buildingCode: string; meterIdentifier: string; valid: boolean }) => {
    if (!payload.valid) {
        return;
    }

    visitAnalytics({
        building: payload.buildingCode,
        meter: payload.meterIdentifier || null,
    });
};

const calculatedConsumptionPoints = computed(() => props.contractData.consumptionPoints.filter((point) => point.confidence.level === 'Calculated' && typeof point.kwhTotal === 'number'));
const calculatedDemandPoints = computed(() => props.contractData.demandPoints.filter((point) => point.confidence.level === 'Calculated' && typeof point.kwDemand === 'number'));
const totalConsumption = computed(() => calculatedConsumptionPoints.value.reduce((total, point) => total + (point.kwhTotal ?? 0), 0));
const peakDemand = computed(() => calculatedDemandPoints.value.reduce<number | null>((peak, point) => {
    if (point.kwDemand === null) {
        return peak;
    }

    return peak === null || point.kwDemand > peak ? point.kwDemand : peak;
}, null));
const topBuilding = computed(() => props.contractData.buildingSummaries[0] ?? null);
const compactDateTime = (value: string | null) => {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(value));
};
const compactPeriodLabel = computed(() => {
    if (!props.analyticsContext.from || !props.analyticsContext.to) {
        return props.analyticsContext.periodLabel;
    }

    return `${compactDateTime(props.analyticsContext.from)} – ${compactDateTime(props.analyticsContext.to)}`;
});
const workspaceTrust = computed<DataTrustIndicator>(() => {
    if (!props.analyticsContext.hasData) {
        return {
            level: 'Incomplete',
            reason: 'No analytics telemetry context is available for this workspace.',
            missingIntervalCount: props.emptyState.missingIntervalCount,
            warning: 'Run the analytics demo scenario before reviewing the workspace.',
        };
    }

    if (props.contractEvidence.incompleteCount > 0) {
        return {
            level: 'Incomplete',
            reason: 'The selected window contains incomplete analytical evidence.',
            missingIntervalCount: props.emptyState.missingIntervalCount,
            warning: 'Review missing intervals before treating this as final evidence.',
        };
    }

    if (props.contractEvidence.unknownCount > 0) {
        return {
            level: 'Unknown',
            reason: 'The selected window contains zero-delta or non-informative analytical evidence.',
            missingIntervalCount: props.emptyState.missingIntervalCount,
            warning: 'Some values require interpretation before comparison.',
        };
    }

    return {
        level: 'Calculated',
        reason: 'Workspace evidence is calculated from the selected analytics contracts.',
        missingIntervalCount: props.emptyState.missingIntervalCount,
        warning: null,
    };
});

const formatNumber = (value: number | null) => {
    if (value === null) {
        return null;
    }

    return Number(value.toFixed(2));
};

const summaryTrust = computed(() => ({
    ...workspaceTrust.value,
    displayLabel: workspaceTrust.value.level === 'Calculated' ? 'Evidence ready' : 'Review needed',
}));
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

                        <StatusChip label="AN-018" tone="success" />
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
                        <ScopePill
                            label="Query"
                            value="Query Synced"
                            tone="neutral"
                            :title="props.queryState.url"
                        />
                        <span
                            v-if="props.queryState.url !== '/analytics'"
                            class="inline-flex items-center rounded-full border bg-muted/30 px-3 py-1 text-[11px] text-muted-foreground"
                            :title="props.queryState.url"
                        >
                            Share URL ready
                        </span>
                    </div>

                    <div class="grid gap-5 xl:grid-cols-2">
                        <AnalyticsContextSelector
                            :building-code="selectedBuildingCode"
                            :meter-identifier="selectedMeterIdentifier"
                            :building-options="props.contextControls.buildingOptions"
                            :meter-options="props.contextControls.meterOptions"
                            :disabled="!props.analyticsContext.hasData"
                            @change="applyContext"
                        />

                        <TimeRangePicker
                            v-model:from="selectedFrom"
                            v-model:to="selectedTo"
                            :timezone-label="props.timeRangeControls.timezoneLabel"
                            :min="props.timeRangeControls.min"
                            :max="props.timeRangeControls.max"
                            title="Investigation Window"
                            description="Choose the historical date range that should drive every analytics contract on this page."
                            from-label="Start date"
                            to-label="End date"
                            :presets="props.timeRangeControls.presets"
                            :disabled="!props.analyticsContext.hasData"
                            @change="applyTimeRange"
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

                                <div class="flex flex-col items-start gap-1 sm:items-end">
                                    <ScopePill label="Evidence" :value="displayContract(section.contract).short" tone="info" />
                                    <span class="text-[11px] text-muted-foreground" :title="displayContract(section.contract).full">
                                        {{ displayContract(section.contract).full }}
                                    </span>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div v-if="props.analyticsContext.hasData" class="space-y-5">
                        <div class="grid gap-5 xl:grid-cols-3">
                            <ConsumptionSummaryCard
                                class="xl:col-span-1"
                                title="Selected Window Consumption"
                                :value="formatNumber(totalConsumption)"
                                :period-label="compactPeriodLabel"
                                :confidence="summaryTrust"
                                :comparison="{
                                    label: 'Top Building',
                                    value: topBuilding?.buildingCode ?? 'Unavailable',
                                    direction: 'none',
                                    context: topBuilding ? `${topBuilding.totalKwh} kWh calculated` : 'No ranked building summary',
                                }"
                                caption="Total calculated consumption for the selected meter and analytical window."
                                source-label="ConsumptionSeriesPoint"
                            />

                            <ConsumptionSummaryCard
                                title="Peak Demand"
                                :value="formatNumber(peakDemand)"
                                unit="kW"
                                :period-label="compactPeriodLabel"
                                :confidence="summaryTrust"
                                :comparison="{
                                    label: 'Demand Evidence',
                                    value: props.contractEvidence.demandPointCount,
                                    direction: 'none',
                                    context: `${props.contractEvidence.calculatedDemandCount} calculated demand points`,
                                }"
                                caption="Highest calculated demand observed in the selected window."
                                source-label="DemandSeriesPoint"
                            />

                            <ConsumptionSummaryCard
                                title="Building Leader"
                                :value="formatNumber(topBuilding?.totalKwh ?? null)"
                                :period-label="compactPeriodLabel"
                                :confidence="summaryTrust"
                                :comparison="{
                                    label: 'Compared Buildings',
                                    value: props.contractEvidence.buildingSummaryCount,
                                    direction: 'none',
                                    context: topBuilding?.buildingCode ? `${topBuilding.buildingCode} is currently ranked first` : 'No building comparison available',
                                }"
                                caption="Highest building consumption summary for this analytical window."
                                source-label="BuildingConsumptionSummary"
                            />
                        </div>

                        <div class="grid gap-5 xl:grid-cols-2">
                            <ConsumptionTrend
                                :points="props.contractData.consumptionPoints"
                                title="Consumption Trend"
                                description="Hourly consumption series for the selected analytical context."
                            />
                            <DemandCurve
                                :points="props.contractData.demandPoints"
                                title="Demand Curve"
                                description="Hourly demand series with peak markers for the selected analytical context."
                            />
                        </div>

                        <BuildingComparisonGrid
                            :summaries="props.contractData.buildingSummaries"
                            title="Building Comparison"
                            description="Ranked building consumption summaries for the same selected analytical window."
                        />

                        <LoadProfileExplorer
                            :context="{
                                meterName: props.analyticsContext.meterIdentifier,
                                buildingCode: props.analyticsContext.buildingCode,
                                siteCode: props.analyticsContext.siteCode,
                                grain: props.analyticsContext.grain,
                                periodLabel: compactPeriodLabel,
                            }"
                            :consumption-points="props.contractData.consumptionPoints"
                            :demand-points="props.contractData.demandPoints"
                        />

                        <AnalyticsExportPanel
                            :title="props.exportPanel.title"
                            :description="props.exportPanel.description"
                            :context="{
                                hasData: props.analyticsContext.hasData,
                                periodLabel: compactPeriodLabel,
                                meterIdentifier: props.analyticsContext.meterIdentifier,
                                buildingCode: props.analyticsContext.buildingCode,
                                siteCode: props.analyticsContext.siteCode,
                                grain: props.analyticsContext.grain,
                            }"
                            :evidence="{
                                consumptionPointCount: props.contractEvidence.consumptionPointCount,
                                demandPointCount: props.contractEvidence.demandPointCount,
                                buildingSummaryCount: props.contractEvidence.buildingSummaryCount,
                                incompleteCount: props.contractEvidence.incompleteCount,
                                unknownCount: props.contractEvidence.unknownCount,
                            }"
                            :actions="exportPanelActions"
                            :preservation-note="props.exportPanel.preservationNote"
                        />
                    </div>

                    <AnalyticsEmptyState
                        v-else
                        :kind="props.emptyState.kind"
                        :title="props.emptyState.title"
                        :description="props.emptyState.description"
                        :context-label="props.emptyState.contextLabel"
                        :source-label="props.emptyState.sourceLabel"
                        :missing-interval-count="props.emptyState.missingIntervalCount"
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
