<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

type ReportType = 'sap' | 'raw' | 'site' | 'consumption' | 'demand';

const props = defineProps<{
    title: string;
    reportType: ReportType;
}>();

const page = usePage();
const csrfToken = page.props.csrfToken as string;

const actionsByType = {
    sap: [
        { label: 'Generate SAP Report', method: 'POST', action: '/generate_sap_report' },
        { label: 'Download SAP Excel', method: 'GET', action: '/generate_sap_report_excel' },
    ],
    raw: [
        { label: 'Generate RAW Report', method: 'POST', action: '/generate_raw_report' },
        { label: 'Download RAW Excel', method: 'GET', action: '/generate_raw_report_excel' },
    ],
    site: [
        { label: 'Generate Site Report', method: 'POST', action: '/generate_site_report' },
        { label: 'Download Site Excel', method: 'GET', action: '/generate_site_report_excel' },
        { label: 'Download Site-As-Built Excel', method: 'GET', action: '/generate_site_as_built_excel' },
    ],
    consumption: [
        { label: 'Generate Consumption Report (Hourly)', method: 'POST', action: '/generate_consumption_report/hourly' },
        { label: 'Generate Consumption Report (Daily)', method: 'POST', action: '/generate_consumption_report/daily' },
        { label: 'Download Consumption Export', method: 'GET', action: '/download_consumption_report' },
    ],
    demand: [
        { label: 'Generate Demand Report (Hourly)', method: 'POST', action: '/generate_demand_report/hourly' },
        { label: 'Generate Demand Report (15-min)', method: 'POST', action: '/generate_demand_report/fifteen' },
        { label: 'Download Demand Export', method: 'GET', action: '/download_demand_report' },
    ],
};

const settingsActions = [
    { label: 'Build Building List', method: 'POST', action: '/generate_building_list' },
    { label: 'Build Meter List', method: 'POST', action: '/generate_meter_list' },
];

const offlineActions = [
    { label: 'Download Offline Gateway', method: 'GET', action: '/download_offline_gateway' },
    { label: 'Download Offline Meter', method: 'GET', action: '/download_offline_meter' },
];

const activeActions = computed(() => actionsByType[props.reportType] ?? []);
</script>

<template>
    <div class="space-y-6">
        <Head :title="props.title" />

        <h1 class="text-2xl font-semibold">{{ props.title }}</h1>
        <p class="text-sm text-gray-600">
            This report module is scaffolded in Laravel 13 while preserving the legacy route surface.
        </p>

        <section class="space-y-2">
            <h2 class="text-lg font-medium">Report actions</h2>
            <div class="flex flex-wrap gap-2">
                <form
                    v-for="action in activeActions"
                    :key="action.action"
                    :method="action.method === 'GET' ? 'GET' : 'POST'"
                    :action="action.action"
                    class="inline"
                >
                    <input v-if="action.method === 'POST'" type="hidden" name="_token" :value="csrfToken" />
                    <button class="rounded-md bg-black px-4 py-2 text-sm font-medium text-white" type="submit">
                        {{ action.label }}
                    </button>
                </form>
            </div>
        </section>

        <section class="space-y-2">
            <h2 class="text-lg font-medium">Report settings helpers</h2>
            <div class="flex flex-wrap gap-2">
                <form
                    v-for="settingAction in settingsActions"
                    :key="settingAction.action"
                    method="POST"
                    :action="settingAction.action"
                    class="inline"
                >
                    <input type="hidden" name="_token" :value="csrfToken" />
                    <button class="rounded-md border px-4 py-2 text-sm font-medium" type="submit">
                        {{ settingAction.label }}
                    </button>
                </form>
            </div>
        </section>

        <section class="space-y-2">
            <h2 class="text-lg font-medium">Offline report helpers</h2>
            <div class="flex flex-wrap gap-2">
                <a
                    v-for="offlineAction in offlineActions"
                    :key="offlineAction.action"
                    :href="offlineAction.action"
                    class="inline-block rounded-md border px-4 py-2 text-sm font-medium"
                >
                    {{ offlineAction.label }}
                </a>
            </div>
        </section>
    </div>
</template>
