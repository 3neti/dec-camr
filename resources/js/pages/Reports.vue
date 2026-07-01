<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import {
    downloadOfflineGateway,
    downloadOfflineMeter,
    generateBuildingList,
    generateMeterList,
} from '@/actions/App/Http/Controllers/ReportController';
import OperatorPage from '@/components/operator/OperatorPage.vue';
import ReportFamilySelector from '@/components/operator/ReportFamilySelector.vue';
import ReportFilterPanel from '@/components/operator/ReportFilterPanel.vue';
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
    tone?: 'primary' | 'secondary';
};

type ReportFilterPanelContract = {
    title: string;
    description?: string;
    sections: FilterSection[];
    actions: FilterAction[];
};

const props = defineProps<{
    title: string;
    reportType: ReportType;
    reportFamilies: ReportFamily[];
    filterPanel: ReportFilterPanelContract;
}>();

const page = usePage<{ csrfToken?: string }>();
const csrfToken = page.props.csrfToken ?? '';

const settingsActions = [
    { label: 'Build Building List', route: generateBuildingList.form() },
    { label: 'Build Meter List', route: generateMeterList.form() },
];

const offlineActions = [
    { label: 'Download Offline Gateway', href: downloadOfflineGateway.url() },
    { label: 'Download Offline Meter', href: downloadOfflineMeter.url() },
];
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
            />

            <div class="grid gap-6">
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
