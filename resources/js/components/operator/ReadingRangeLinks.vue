<script setup lang="ts">
import { computed } from 'vue';
import AnalyticsController from '@/actions/App/Http/Controllers/AnalyticsController';
import DrilldownActions from './DrilldownActions.vue';

const props = defineProps<{
    meterIdentifier: string;
    buildingCode?: string | null;
}>();

const isoDate = (date: Date): string => date.toISOString().slice(0, 10);

const startOfWeek = (date: Date): Date => {
    const copy = new Date(date);
    const day = copy.getDay() || 7;
    copy.setDate(copy.getDate() - day + 1);

    return copy;
};

const analyticsUrl = (from?: string, to?: string): string => {
    const query: Record<string, string> = {
        meter: props.meterIdentifier,
    };

    if (props.buildingCode) {
        query.building = props.buildingCode;
        query.comparison = 'selected';
    }

    if (from) {
        query.from = from;
    }

    if (to) {
        query.to = to;
    }

    return AnalyticsController.url({ query });
};

const actions = computed(() => {
    const today = new Date();
    const yearStart = new Date(today.getFullYear(), 0, 1);
    const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
    const weekStart = startOfWeek(today);
    const end = isoDate(today);

    return [
        { label: 'All readings', href: analyticsUrl(), primary: true },
        { label: 'This year', href: analyticsUrl(isoDate(yearStart), end) },
        { label: 'This month', href: analyticsUrl(isoDate(monthStart), end) },
        { label: 'This week', href: analyticsUrl(isoDate(weekStart), end) },
        { label: 'Today', href: analyticsUrl(end, end) },
    ];
});
</script>

<template>
    <DrilldownActions :actions="actions" />
</template>
