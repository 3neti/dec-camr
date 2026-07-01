<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import OperatorPage from '@/components/operator/OperatorPage.vue';
import ReportFamilySelector from '@/components/operator/ReportFamilySelector.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

type ReportType = 'sap' | 'raw' | 'site' | 'consumption' | 'demand';

type ReportAction = {
    label: string;
    method: 'GET' | 'POST';
    action: string;
};

type ReportFamily = {
    id: string;
    label: string;
    description: string;
    href: string;
    active: boolean;
};

const props = defineProps<{
    title: string;
    reportType: ReportType;
    reportFamilies: ReportFamily[];
}>();

const page = usePage<{ csrfToken?: string }>();
const csrfToken = page.props.csrfToken ?? '';

const actionSets: Record<
    ReportType,
    {
        description: string;
        actions: ReportAction[];
    }
> = {
    sap: {
        description: 'SAP exports remain on their legacy route surface while the operator console clarifies the available actions.',
        actions: [
            { label: 'Generate SAP Report', method: 'POST', action: '/generate_sap_report' },
            { label: 'Download SAP Excel', method: 'GET', action: '/generate_sap_report_excel' },
        ],
    },
    raw: {
        description: 'Raw Data keeps the existing on-screen generation and workbook export flow.',
        actions: [
            { label: 'Generate RAW Report', method: 'POST', action: '/generate_raw_report' },
            { label: 'Download RAW Excel', method: 'GET', action: '/generate_raw_report_excel' },
        ],
    },
    site: {
        description: 'Building workflows continue to carry the site report surface, Site As-Built export, and related helper downloads.',
        actions: [
            { label: 'Generate Site Report', method: 'POST', action: '/generate_site_report' },
            { label: 'Download Site Excel', method: 'GET', action: '/generate_site_report_excel' },
            { label: 'Download Site-As-Built Excel', method: 'GET', action: '/generate_site_as_built_excel' },
        ],
    },
    consumption: {
        description: 'KWh Consumption keeps the hourly, daily, and workbook paths operators already know.',
        actions: [
            { label: 'Generate Consumption Report (Hourly)', method: 'POST', action: '/generate_consumption_report/hourly' },
            { label: 'Generate Consumption Report (Daily)', method: 'POST', action: '/generate_consumption_report/daily' },
            { label: 'Download Consumption Export', method: 'GET', action: '/download_consumption_report' },
        ],
    },
    demand: {
        description: 'KW Demand continues to expose hourly, 15-minute, and workbook flows on the existing report routes.',
        actions: [
            { label: 'Generate Demand Report (Hourly)', method: 'POST', action: '/generate_demand_report/hourly' },
            { label: 'Generate Demand Report (15-min)', method: 'POST', action: '/generate_demand_report/fifteen' },
            { label: 'Download Demand Export', method: 'GET', action: '/download_demand_report' },
        ],
    },
};

const settingsActions: ReportAction[] = [
    { label: 'Build Building List', method: 'POST', action: '/generate_building_list' },
    { label: 'Build Meter List', method: 'POST', action: '/generate_meter_list' },
];

const offlineActions: ReportAction[] = [
    { label: 'Download Offline Gateway', method: 'GET', action: '/download_offline_gateway' },
    { label: 'Download Offline Meter', method: 'GET', action: '/download_offline_meter' },
];

const currentActionSet = computed(() => actionSets[props.reportType]);
</script>

<template>
    <OperatorPage
        :title="props.title"
        :description="'Legacy report names remain recognizable while the operator console groups each report family more clearly.'"
    >
        <ReportFamilySelector :items="props.reportFamilies" />

        <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <Card class="py-5">
                <CardHeader class="px-5 pb-0">
                    <CardTitle>{{ props.title }}</CardTitle>
                    <CardDescription>{{ currentActionSet.description }}</CardDescription>
                </CardHeader>

                <CardContent class="flex flex-wrap gap-3 px-5">
                    <form
                        v-for="action in currentActionSet.actions"
                        :key="action.action"
                        :method="action.method === 'GET' ? 'GET' : 'POST'"
                        :action="action.action"
                    >
                        <input v-if="action.method === 'POST'" type="hidden" name="_token" :value="csrfToken" />
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-md border px-4 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                        >
                            {{ action.label }}
                        </button>
                    </form>
                </CardContent>
            </Card>

            <div class="grid gap-6">
                <Card class="py-5">
                    <CardHeader class="px-5 pb-0">
                        <CardTitle>Report settings helpers</CardTitle>
                        <CardDescription>Dependent selector endpoints remain available for the legacy report filters.</CardDescription>
                    </CardHeader>

                    <CardContent class="flex flex-wrap gap-3 px-5">
                        <form
                            v-for="settingAction in settingsActions"
                            :key="settingAction.action"
                            method="POST"
                            :action="settingAction.action"
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
                            :key="offlineAction.action"
                            :href="offlineAction.action"
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
