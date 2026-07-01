<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    downloadOfflineGateway,
    downloadOfflineMeter,
    generateBuildingList,
    generateMeterList,
} from '@/actions/App/Http/Controllers/ReportController';
import OperatorPage from '@/components/operator/OperatorPage.vue';
import ReportFamilySelector from '@/components/operator/ReportFamilySelector.vue';
import ReportFilterPanel from '@/components/operator/ReportFilterPanel.vue';
import ReportPreviewSummary from '@/components/operator/ReportPreviewSummary.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

type ReportType = 'sap' | 'raw' | 'site' | 'consumption' | 'demand';

type ReportFamily = {
    id: string;
    label: string;
    description: string;
    href: string;
    active: boolean;
};

type FilterField = {
    id: string;
    name: string;
    label: string;
    type?: 'text' | 'number' | 'date' | 'time' | 'select';
    placeholder?: string;
    value?: string | number | null;
};

type FilterSection = {
    id: string;
    title: string;
    description?: string;
    fields: FilterField[];
};

type FilterAction = {
    id: string;
    label: string;
    method: 'get' | 'post';
    action: string;
    kind: 'submit' | 'export';
    tone?: 'primary' | 'secondary';
};

type ReportFilterPanelContract = {
    title: string;
    description?: string;
    sections: FilterSection[];
    actions: FilterAction[];
};

type ReportPreviewSummaryContract = {
    title: string;
    description?: string;
    statusLabel: string;
    rowsLabel: string;
    unitsLabel: string;
    scopeMode: 'site' | 'site-meter';
    rangeMode: 'none' | 'date' | 'datetime';
    emptyTitle: string;
    emptyDescription: string;
    notes: string[];
};

const props = defineProps<{
    title: string;
    reportType: ReportType;
    reportFamilies: ReportFamily[];
    filterPanel: ReportFilterPanelContract;
    previewSummary: ReportPreviewSummaryContract;
}>();

const page = usePage<{ csrfToken?: string }>();
const csrfToken = page.props.csrfToken ?? '';
const filterValues = ref<Record<string, string>>(
    Object.fromEntries(
        props.filterPanel.sections.flatMap((section) =>
            section.fields.map((field) => [field.name, field.value === null || field.value === undefined ? '' : String(field.value)]),
        ),
    ),
);

const settingsActions = [
    { label: 'Build Building List', route: generateBuildingList.form() },
    { label: 'Build Meter List', route: generateMeterList.form() },
];

const offlineActions = [
    { label: 'Download Offline Gateway', href: downloadOfflineGateway.url() },
    { label: 'Download Offline Meter', href: downloadOfflineMeter.url() },
];

const syncFilterValues = (values: Record<string, string>) => {
    filterValues.value = values;
};

const activeFamilyLabel = computed(
    () => props.reportFamilies.find((family) => family.active)?.label ?? props.title,
);

const previewScopeLabel = computed(() => {
    const siteId = filterValues.value.site_id?.trim() || 'No site selected';

    if (props.previewSummary.scopeMode === 'site-meter') {
        const meterId = filterValues.value.meter_id?.trim() || 'No meter selected';

        return `Building / Site ID: ${siteId} • Meter ID: ${meterId}`;
    }

    return `Building / Site ID: ${siteId}`;
});

const previewRangeLabel = computed(() => {
    if (props.previewSummary.rangeMode === 'none') {
        return 'No date range required for this report family.';
    }

    const startDate = filterValues.value.start_date?.trim() || 'Start date not selected';
    const endDate = filterValues.value.end_date?.trim() || 'End date not selected';

    if (props.previewSummary.rangeMode === 'date') {
        return `${startDate} -> ${endDate}`;
    }

    const startTime = filterValues.value.start_time?.trim() || 'Start time not selected';
    const endTime = filterValues.value.end_time?.trim() || 'End time not selected';

    return `${startDate} ${startTime} -> ${endDate} ${endTime}`;
});

const previewMetrics = computed(() => [
    {
        label: 'Report Family',
        value: activeFamilyLabel.value,
    },
    {
        label: 'Scope',
        value: previewScopeLabel.value,
    },
    {
        label: 'Range',
        value: previewRangeLabel.value,
    },
    {
        label: 'Rows',
        value: props.previewSummary.rowsLabel,
    },
    {
        label: 'Units',
        value: props.previewSummary.unitsLabel,
    },
]);
</script>

<template>
    <OperatorPage
        :title="props.title"
        :description="'Legacy report names remain recognizable while the operator console groups each report family more clearly.'"
    >
        <ReportFamilySelector :items="props.reportFamilies" />

        <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <ReportFilterPanel
                :title="props.filterPanel.title"
                :description="props.filterPanel.description"
                :sections="props.filterPanel.sections"
                :actions="props.filterPanel.actions"
                :csrf-token="csrfToken"
                @state-change="syncFilterValues"
            />

            <div class="grid gap-6">
                <ReportPreviewSummary
                    :title="props.previewSummary.title"
                    :description="props.previewSummary.description"
                    :status-label="props.previewSummary.statusLabel"
                    :metrics="previewMetrics"
                    :empty-title="props.previewSummary.emptyTitle"
                    :empty-description="props.previewSummary.emptyDescription"
                    :notes="props.previewSummary.notes"
                />

                <Card class="py-5">
                    <CardHeader class="px-5 pb-0">
                        <CardTitle>Report settings helpers</CardTitle>
                        <CardDescription>Dependent selector endpoints remain available for the legacy report filters.</CardDescription>
                    </CardHeader>

                    <CardContent class="flex flex-wrap gap-3 px-5">
                        <form
                            v-for="settingAction in settingsActions"
                            :key="settingAction.route.action"
                            :action="settingAction.route.action"
                            :method="settingAction.route.method.toUpperCase()"
                        >
                            <input type="hidden" name="_token" :value="csrfToken" />
                            <button
                                type="submit"
                                class="inline-flex items-center rounded-md border px-4 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                            >
                                {{ settingAction.label }}
                            </button>
                        </form>
                    </CardContent>
                </Card>

                <Card class="py-5">
                    <CardHeader class="px-5 pb-0">
                        <CardTitle>Offline and recovery helpers</CardTitle>
                        <CardDescription>Offline workbook downloads remain available while operators validate gateway and meter follow-up flows.</CardDescription>
                    </CardHeader>

                    <CardContent class="flex flex-wrap gap-3 px-5">
                        <a
                            v-for="offlineAction in offlineActions"
                            :key="offlineAction.href"
                            :href="offlineAction.href"
                            class="inline-flex items-center rounded-md border px-4 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                        >
                            {{ offlineAction.label }}
                        </a>
                    </CardContent>
                </Card>
            </div>
        </div>
    </OperatorPage>
</template>
