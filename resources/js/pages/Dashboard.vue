<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { building } from '@/actions/App/Http/Controllers/BuildingController';
import { gateway } from '@/actions/App/Http/Controllers/GatewayController';
import { meter } from '@/actions/App/Http/Controllers/MeterController';
import {
    consumptionReport,
    demandReport,
    rawReport,
    sapReport,
    siteReport,
} from '@/actions/App/Http/Controllers/ReportController';
import { user } from '@/actions/App/Http/Controllers/UserController';
import AttentionList from '@/components/operator/AttentionList.vue';
import GatewayHealthList from '@/components/operator/GatewayHealthList.vue';
import KpiCard from '@/components/operator/KpiCard.vue';
import OperatorPage from '@/components/operator/OperatorPage.vue';
import QuickActionGrid from '@/components/operator/QuickActionGrid.vue';
import RecentTelemetryList from '@/components/operator/RecentTelemetryList.vue';
import StatusChip from '@/components/operator/StatusChip.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { site } from '@/routes';

type ReportState = 'ready' | 'partial' | 'empty';
type TelemetryStatus = 'online' | 'stale' | 'offline';

type DashboardProps = {
    context: {
        user: {
            id: number;
            name: string;
            role: string;
            access: string;
        };
        generatedAt: string;
    };
    gatewaySummary: {
        total: number;
        online: number;
        stale: number;
        offline: number;
    };
    meterSummary: {
        total: number;
        active: number;
        online: number;
        stale: number;
        offline: number;
    };
    telemetrySummary: {
        recentReadings: number;
        activeMeters: number;
        lastReceivedAt: string | null;
        recentTelemetry: Array<{
            id: string;
            meterId: string;
            meterName: string | null;
            siteCode: string | null;
            locationId: string | null;
            receivedAt: string;
            status: TelemetryStatus;
        }>;
    };
    pendingUpdateSummary: {
        total: number;
        csv: number;
        location: number;
        ssh: number;
        forceLoadProfile: number;
    };
    gatewayHealth: Array<{
        id: number;
        gatewaySn: string;
        gatewayMac: string;
        description: string | null;
        siteCode: string | null;
        lastLogUpdate: string | null;
        status: TelemetryStatus;
        softRev: string | null;
        meterCount: number;
        activeMeterCount: number;
        pendingUpdates: string[];
        hasPendingUpdates: boolean;
    }>;
    reportReadiness: {
        raw: {
            state: string;
            recentReadings: number;
            lastReceivedAt: string | null;
        };
        consumption: {
            state: string;
            recentReadings: number;
            activeMeters: number;
        };
        demand: {
            state: string;
            recentReadings: number;
            activeMeters: number;
        };
        sap: {
            state: string;
            sitesWithRecentTelemetry: number;
            recentReadings: number;
        };
        site: {
            state: string;
            sitesWithRecentTelemetry: number;
            recentReadings: number;
        };
    };
};

type AttentionSeverity = 'critical' | 'warning' | 'info';

type AttentionItem = {
    id: string;
    title: string;
    description: string;
    severity: AttentionSeverity;
    context?: string;
    href?: ReturnType<typeof site>;
    actionLabel?: string;
};

const props = defineProps<DashboardProps>();

const reportTone = {
    ready: 'success',
    partial: 'warning',
    empty: 'danger',
} as const;

const reportLabel = {
    ready: 'Ready',
    partial: 'Partial',
    empty: 'Empty',
} as const;

const gatewayTone = computed(() => {
    if (props.gatewaySummary.offline > 0) {
        return 'danger';
    }

    if (props.gatewaySummary.stale > 0) {
        return 'warning';
    }

    return 'success';
});

const meterTone = computed(() => {
    if (props.meterSummary.offline > 0) {
        return 'danger';
    }

    if (props.meterSummary.stale > 0) {
        return 'warning';
    }

    return 'success';
});

const pendingUpdateTone = computed(() => (props.pendingUpdateSummary.total > 0 ? 'warning' : 'success'));

const reportEntries = computed(() => [
    {
        key: 'raw',
        label: 'Raw',
        href: rawReport(),
        state: props.reportReadiness.raw.state as ReportState,
        summary: `${props.reportReadiness.raw.recentReadings} readings in the report window`,
    },
    {
        key: 'consumption',
        label: 'Consumption',
        href: consumptionReport(),
        state: props.reportReadiness.consumption.state as ReportState,
        summary: `${props.reportReadiness.consumption.activeMeters} active meters with ${props.reportReadiness.consumption.recentReadings} recent readings`,
    },
    {
        key: 'demand',
        label: 'Demand',
        href: demandReport(),
        state: props.reportReadiness.demand.state as ReportState,
        summary: `${props.reportReadiness.demand.activeMeters} active meters with ${props.reportReadiness.demand.recentReadings} recent readings`,
    },
    {
        key: 'sap',
        label: 'SAP',
        href: sapReport(),
        state: props.reportReadiness.sap.state as ReportState,
        summary: `${props.reportReadiness.sap.sitesWithRecentTelemetry} sites with report-ready telemetry`,
    },
    {
        key: 'site',
        label: 'Site',
        href: siteReport(),
        state: props.reportReadiness.site.state as ReportState,
        summary: `${props.reportReadiness.site.sitesWithRecentTelemetry} sites with current building data`,
    },
]);

const readyReportCount = computed(() => reportEntries.value.filter((entry) => entry.state === 'ready').length);

const reportReadinessTone = computed(() => {
    if (readyReportCount.value === reportEntries.value.length) {
        return 'success';
    }

    if (reportEntries.value.some((entry) => entry.state === 'empty')) {
        return 'warning';
    }

    return 'neutral';
});

const roleSummary = computed(() => {
    const role = props.context.user.role.trim();
    const access = props.context.user.access.trim();

    return `${role} operator context with ${access} access. Use this console to confirm health, identify the next operational issue, and drill into maintenance or reporting workflows.`;
});

const lastTelemetrySummary = computed(() => {
    if (!props.telemetrySummary.lastReceivedAt) {
        return 'No recent telemetry received';
    }

    return `${formatRelativeAge(props.telemetrySummary.lastReceivedAt)} • ${formatTimestamp(props.telemetrySummary.lastReceivedAt)}`;
});

const attentionItems = computed<AttentionItem[]>(() => {
    const items: AttentionItem[] = [];

    if (props.gatewaySummary.offline > 0) {
        items.push({
            id: 'gateway-offline',
            title: `${props.gatewaySummary.offline} gateway${pluralize(props.gatewaySummary.offline)} offline`,
            description: 'Communication is beyond the offline threshold. Inspect the affected gateway fleet before telemetry or update work drifts further.',
            severity: 'critical',
            context: 'Gateway health',
            href: gateway(),
            actionLabel: 'Open gateways',
        });
    }

    if (props.gatewaySummary.stale > 0) {
        items.push({
            id: 'gateway-stale',
            title: `${props.gatewaySummary.stale} gateway${pluralize(props.gatewaySummary.stale)} stale`,
            description: 'These gateways are still reporting but are outside the healthy freshness window. Review them before they degrade into full outages.',
            severity: 'warning',
            context: 'Gateway health',
            href: gateway(),
            actionLabel: 'Review stale gateways',
        });
    }

    if (props.meterSummary.offline + props.meterSummary.stale > 0) {
        const affectedMeters = props.meterSummary.offline + props.meterSummary.stale;

        items.push({
            id: 'meter-health',
            title: `${affectedMeters} meter${pluralize(affectedMeters)} need telemetry follow-up`,
            description: `${props.meterSummary.offline} offline and ${props.meterSummary.stale} stale active meters are reducing operator confidence in current readings.`,
            severity: props.meterSummary.offline > 0 ? 'critical' : 'warning',
            context: 'Meter health',
            href: meter(),
            actionLabel: 'Inspect meters',
        });
    }

    if (props.pendingUpdateSummary.total > 0) {
        items.push({
            id: 'pending-updates',
            title: `${props.pendingUpdateSummary.total} pending update flag${pluralize(props.pendingUpdateSummary.total)}`,
            description: `${props.pendingUpdateSummary.csv} CSV, ${props.pendingUpdateSummary.location} location, ${props.pendingUpdateSummary.forceLoadProfile} force-LP, and ${props.pendingUpdateSummary.ssh} SSH actions are waiting for pickup or review.`,
            severity: 'warning',
            context: 'Gateway operations',
            href: gateway(),
            actionLabel: 'Review pending updates',
        });
    }

    if (readyReportCount.value < reportEntries.value.length) {
        items.push({
            id: 'report-readiness',
            title: `${reportEntries.value.length - readyReportCount.value} report famil${reportEntries.value.length - readyReportCount.value === 1 ? 'y is' : 'ies are'} not fully ready`,
            description: 'Representative telemetry exists, but at least one report surface is still partial or empty for the current readiness window.',
            severity: reportEntries.value.some((entry) => entry.state === 'empty') ? 'warning' : 'info',
            context: 'Report readiness',
            href: demandReport(),
            actionLabel: 'Open reports',
        });
    }

    return items;
});

const recentTelemetryItems = computed(() =>
    props.telemetrySummary.recentTelemetry.map((item) => ({
        ...item,
        href: meter(),
    })),
);

const gatewayHealthItems = computed(() =>
    props.gatewayHealth.map((item) => ({
        ...item,
        href: gateway(),
    })),
);

const quickActions = computed(() => {
    const role = props.context.user.role.toLowerCase();

    if (role.includes('analyst')) {
        return [
            { id: 'consumption-report', title: 'Consumption Report', description: 'Validate current telemetry coverage before export.', href: consumptionReport() },
            { id: 'demand-report', title: 'Demand Report', description: 'Check hourly and 15-minute demand readiness.', href: demandReport() },
            { id: 'raw-report', title: 'Raw Report', description: 'Inspect the latest raw telemetry window.', href: rawReport() },
            { id: 'site-report', title: 'Site Report', description: 'Review site/building report availability.', href: siteReport() },
        ];
    }

    if (role.includes('maintenance')) {
        return [
            { id: 'site', title: 'Site Maintenance', description: 'Inspect site, building, and access context.', href: site() },
            { id: 'building', title: 'Building Inventory', description: 'Review building and energy-room structures.', href: building() },
            { id: 'gateway', title: 'Gateway Maintenance', description: 'Open gateway records and update flags.', href: gateway() },
            { id: 'meter', title: 'Meter Inventory', description: 'Review meter assignments and status.', href: meter() },
        ];
    }

    if (role.includes('operation')) {
        return [
            { id: 'gateway', title: 'Gateway Health', description: 'Investigate stale and offline gateway conditions.', href: gateway() },
            { id: 'meter', title: 'Meter Health', description: 'Track stale or missing telemetry at the meter level.', href: meter() },
            { id: 'raw-report', title: 'Raw Telemetry', description: 'Confirm the latest ingestion window.', href: rawReport() },
            { id: 'site', title: 'Site Context', description: 'Cross-check affected sites and buildings.', href: site() },
        ];
    }

    return [
        { id: 'site', title: 'Site Management', description: 'Enter the primary CAMR operator home.', href: site() },
        { id: 'gateway', title: 'Gateway Maintenance', description: 'Review gateway state, update flags, and routing context.', href: gateway() },
        { id: 'meter', title: 'Meter Inventory', description: 'Inspect active meters and their telemetry posture.', href: meter() },
        { id: 'user', title: 'User Access', description: 'Review scoped access and administrative user workflows.', href: user() },
    ];
});

function pluralize(value: number): string {
    return value === 1 ? '' : 's';
}

function formatTimestamp(value: string | null): string {
    if (!value) {
        return 'No telemetry yet';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime())
        ? value
        : new Intl.DateTimeFormat(undefined, {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
              hour: 'numeric',
              minute: '2-digit',
          }).format(date);
}

function formatRelativeAge(value: string | null): string {
    if (!value) {
        return 'No recent telemetry';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    const diffMinutes = Math.round((date.getTime() - Date.now()) / 60000);
    const formatter = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });

    if (Math.abs(diffMinutes) < 60) {
        return formatter.format(diffMinutes, 'minute');
    }

    const diffHours = Math.round(diffMinutes / 60);

    if (Math.abs(diffHours) < 24) {
        return formatter.format(diffHours, 'hour');
    }

    return formatter.format(Math.round(diffHours / 24), 'day');
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Site',
                href: site(),
            },
        ],
    },
});
</script>

<template>
    <OperatorPage
        title="Dashboard"
        heading="CAMR Operator Console"
        :description="roleSummary"
    >
        <section class="overflow-hidden rounded-2xl border bg-linear-to-br from-slate-50 to-white shadow-sm dark:from-slate-950 dark:to-slate-900">
            <div class="flex flex-col gap-5 p-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <StatusChip :label="props.context.user.role" tone="neutral" />
                        <StatusChip :label="props.context.user.access" tone="neutral" />
                    </div>

                    <div class="space-y-2">
                        <h2 class="text-2xl font-semibold tracking-tight text-foreground">
                            {{ props.context.user.name }}
                        </h2>
                        <p class="max-w-3xl text-sm leading-6 text-muted-foreground">
                            Generated from persisted CAMR state at {{ formatTimestamp(props.context.generatedAt) }}.
                            Health, pending updates, and report readiness below are derived from seeded and simulated operational data.
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border bg-background/80 px-4 py-3 shadow-xs">
                        <p class="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground/80">
                            Telemetry freshness
                        </p>
                        <p class="mt-2 text-sm font-semibold text-foreground">
                            {{ lastTelemetrySummary }}
                        </p>
                    </div>
                    <div class="rounded-xl border bg-background/80 px-4 py-3 shadow-xs">
                        <p class="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground/80">
                            Pending operator work
                        </p>
                        <p class="mt-2 text-sm font-semibold text-foreground">
                            {{ attentionItems.length }} active queue item{{ pluralize(attentionItems.length) }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <KpiCard
                title="Gateways"
                eyebrow="Fleet health"
                :value="props.gatewaySummary.total"
                :trend-label="`${props.gatewaySummary.online} online`"
                :caption="`${props.gatewaySummary.stale} stale • ${props.gatewaySummary.offline} offline`"
                :tone="gatewayTone"
                description="Current gateway fleet visibility."
            >
                <template #aside>
                    <Link
                        :href="gateway()"
                        class="inline-flex items-center rounded-md border px-3 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                    >
                        Open
                    </Link>
                </template>
            </KpiCard>

            <KpiCard
                title="Meters"
                eyebrow="Telemetry coverage"
                :value="props.meterSummary.total"
                :trend-label="`${props.meterSummary.active} active`"
                :caption="`${props.meterSummary.stale} stale • ${props.meterSummary.offline} offline`"
                :tone="meterTone"
                description="Meter fleet availability across active devices."
            >
                <template #aside>
                    <Link
                        :href="meter()"
                        class="inline-flex items-center rounded-md border px-3 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                    >
                        Open
                    </Link>
                </template>
            </KpiCard>

            <KpiCard
                title="Telemetry"
                eyebrow="Last 30 minutes"
                :value="props.telemetrySummary.recentReadings"
                :trend-label="`${props.telemetrySummary.activeMeters} active meters`"
                :caption="lastTelemetrySummary"
                :tone="props.telemetrySummary.recentReadings > 0 ? 'success' : 'warning'"
                description="Recent readings flowing through the current window."
            >
                <template #aside>
                    <Link
                        :href="rawReport()"
                        class="inline-flex items-center rounded-md border px-3 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                    >
                        Review
                    </Link>
                </template>
            </KpiCard>

            <KpiCard
                title="Pending Updates"
                eyebrow="Gateway actions"
                :value="props.pendingUpdateSummary.total"
                :trend-label="`${props.pendingUpdateSummary.csv} CSV • ${props.pendingUpdateSummary.location} location`"
                :caption="`${props.pendingUpdateSummary.forceLoadProfile} force LP • ${props.pendingUpdateSummary.ssh} SSH`"
                :tone="pendingUpdateTone"
                description="Protocol-side work waiting for pickup or reset."
            >
                <template #aside>
                    <Link
                        :href="gateway()"
                        class="inline-flex items-center rounded-md border px-3 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                    >
                        Review
                    </Link>
                </template>
            </KpiCard>

            <KpiCard
                title="Reports Ready"
                eyebrow="Current data windows"
                :value="`${readyReportCount}/${reportEntries.length}`"
                :trend-label="readyReportCount === reportEntries.length ? 'All ready' : 'Needs review'"
                :caption="`${reportEntries.length - readyReportCount} report families still partial or empty`"
                :tone="reportReadinessTone"
                description="Representative report families with usable current telemetry."
            >
                <template #aside>
                    <Link
                        :href="demandReport()"
                        class="inline-flex items-center rounded-md border px-3 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                    >
                        Open
                    </Link>
                </template>
            </KpiCard>

            <KpiCard
                title="Operator Scope"
                eyebrow="Current session"
                :value="props.context.user.access"
                :trend-label="props.context.user.role"
                :caption="`Generated ${formatRelativeAge(props.context.generatedAt)}`"
                tone="neutral"
                description="Current user posture for the console surface."
            >
                <template #aside>
                    <Link
                        :href="site()"
                        class="inline-flex items-center rounded-md border px-3 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                    >
                        Home
                    </Link>
                </template>
            </KpiCard>
        </section>

        <section class="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
            <AttentionList
                :items="attentionItems"
                title="Needs Attention"
                description="Severity-sorted operational issues that should drive the next operator action."
            />

            <div class="grid gap-4">
                <Card class="py-5">
                    <CardHeader class="px-5 pb-0">
                        <CardTitle>Operations Snapshot</CardTitle>
                        <CardDescription>Current health posture from persisted fleet and telemetry state.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 px-5">
                        <div class="flex items-center justify-between gap-3 rounded-lg border p-3">
                            <div>
                                <p class="text-sm font-medium text-foreground">Gateway posture</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ props.gatewaySummary.online }} online • {{ props.gatewaySummary.stale }} stale • {{ props.gatewaySummary.offline }} offline
                                </p>
                            </div>
                            <StatusChip :label="gatewayTone === 'danger' ? 'Action required' : gatewayTone === 'warning' ? 'Watch' : 'Healthy'" :tone="gatewayTone" />
                        </div>

                        <div class="flex items-center justify-between gap-3 rounded-lg border p-3">
                            <div>
                                <p class="text-sm font-medium text-foreground">Meter posture</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ props.meterSummary.active }} active • {{ props.meterSummary.stale }} stale • {{ props.meterSummary.offline }} offline
                                </p>
                            </div>
                            <StatusChip :label="meterTone === 'danger' ? 'Action required' : meterTone === 'warning' ? 'Watch' : 'Healthy'" :tone="meterTone" />
                        </div>

                        <div class="flex items-center justify-between gap-3 rounded-lg border p-3">
                            <div>
                                <p class="text-sm font-medium text-foreground">Latest telemetry</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ props.telemetrySummary.recentReadings }} recent readings from {{ props.telemetrySummary.activeMeters }} active meters
                                </p>
                            </div>
                            <StatusChip :label="props.telemetrySummary.recentReadings > 0 ? 'Current' : 'Quiet'" :tone="props.telemetrySummary.recentReadings > 0 ? 'success' : 'warning'" />
                        </div>
                    </CardContent>
                </Card>

                <RecentTelemetryList
                    :items="recentTelemetryItems"
                    description="Latest readings, freshness, and meter context without loading the full telemetry history."
                />

                <GatewayHealthList :items="gatewayHealthItems" />

                <Card class="py-5">
                    <CardHeader class="px-5 pb-0">
                        <CardTitle>Report Readiness</CardTitle>
                        <CardDescription>Representative report families and their current data posture.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3 px-5">
                        <div
                            v-for="entry in reportEntries"
                            :key="entry.key"
                            class="flex items-start justify-between gap-3 rounded-lg border p-3"
                        >
                            <div class="space-y-1">
                                <p class="text-sm font-medium text-foreground">
                                    {{ entry.label }}
                                </p>
                                <p class="text-sm leading-6 text-muted-foreground">
                                    {{ entry.summary }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <StatusChip :label="reportLabel[entry.state]" :tone="reportTone[entry.state]" />
                                <Link
                                    :href="entry.href"
                                    class="text-sm font-medium text-foreground underline-offset-4 transition hover:underline"
                                >
                                    Open
                                </Link>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <QuickActionGrid :items="quickActions" />
            </div>
        </section>
    </OperatorPage>
</template>
