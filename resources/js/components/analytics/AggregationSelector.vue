<script setup lang="ts">
import { computed } from 'vue';
import { Label } from '@/components/ui/label';

type AnalyticsMetric = 'consumption' | 'demand' | 'custom';

type SelectorOption = {
    value: string;
    label: string;
    description?: string;
    supports?: AnalyticsMetric[];
};

const defaultGrainOptions: SelectorOption[] = [
    {
        value: 'hourly',
        label: 'Hourly',
        description: 'Report-compatible hourly analytical windows.',
        supports: ['consumption', 'demand'],
    },
    {
        value: 'daily',
        label: 'Daily',
        description: 'Daily consumption windows using report-compatible boundaries.',
        supports: ['consumption'],
    },
    {
        value: 'fifteen-minute',
        label: '15-minute',
        description: 'Demand-compatible fifteen-minute windows.',
        supports: ['demand'],
    },
];

const defaultAggregationOptions: SelectorOption[] = [
    {
        value: 'sum',
        label: 'Sum',
        description: 'Total value across the selected window.',
        supports: ['consumption'],
    },
    {
        value: 'average',
        label: 'Average',
        description: 'Average value across the selected window.',
        supports: ['consumption', 'demand'],
    },
    {
        value: 'maximum',
        label: 'Maximum',
        description: 'Highest value in the selected window.',
        supports: ['consumption', 'demand'],
    },
    {
        value: 'minimum',
        label: 'Minimum',
        description: 'Lowest value in the selected window.',
        supports: ['consumption', 'demand'],
    },
    {
        value: 'peak',
        label: 'Peak',
        description: 'Peak demand marker for the selected window.',
        supports: ['demand'],
    },
];

const props = withDefaults(
    defineProps<{
        grain: string;
        aggregation: string;
        metric?: AnalyticsMetric;
        title?: string;
        description?: string;
        grainLabel?: string;
        aggregationLabel?: string;
        grainOptions?: SelectorOption[];
        aggregationOptions?: SelectorOption[];
        disabled?: boolean;
    }>(),
    {
        metric: 'custom',
        title: 'Aggregation',
        description: 'Choose the analytical grain and aggregation before requesting series data.',
        grainLabel: 'Grain',
        aggregationLabel: 'Aggregation',
        grainOptions: () => defaultGrainOptions,
        aggregationOptions: () => defaultAggregationOptions,
        disabled: false,
    },
);

const emit = defineEmits<{
    'update:grain': [value: string];
    'update:aggregation': [value: string];
    change: [payload: { grain: string; aggregation: string; metric: AnalyticsMetric; valid: boolean }];
}>();

const isSupported = (option: SelectorOption | undefined) => {
    if (!option) {
        return false;
    }

    if (props.metric === 'custom' || option.supports === undefined || option.supports.length === 0) {
        return true;
    }

    return option.supports.includes(props.metric);
};

const selectedGrain = computed(() => props.grainOptions.find((option) => option.value === props.grain));
const selectedAggregation = computed(() => props.aggregationOptions.find((option) => option.value === props.aggregation));

const validationMessageFor = (grain: string, aggregation: string) => {
    const grainOption = props.grainOptions.find((option) => option.value === grain);
    const aggregationOption = props.aggregationOptions.find((option) => option.value === aggregation);

    if (!grain) {
        return 'Choose an analytical grain.';
    }

    if (!aggregation) {
        return 'Choose an aggregation method.';
    }

    if (!grainOption) {
        return `Unsupported analytical grain [${grain}].`;
    }

    if (!aggregationOption) {
        return `Unsupported aggregation method [${aggregation}].`;
    }

    if (!isSupported(grainOption)) {
        return `${grainOption.label} is not supported for ${props.metric} analytics.`;
    }

    if (!isSupported(aggregationOption)) {
        return `${aggregationOption.label} is not supported for ${props.metric} analytics.`;
    }

    return '';
};

const validationMessage = computed(() => validationMessageFor(props.grain, props.aggregation));
const isValid = computed(() => validationMessage.value === '');
const selectedContractLabel = computed(() => {
    if (props.metric === 'consumption') {
        return 'ConsumptionSeriesPoint';
    }

    if (props.metric === 'demand') {
        return 'DemandSeriesPoint';
    }

    return 'Caller-provided analytics contract';
});

const emitChange = (grain: string, aggregation: string) => {
    emit('change', {
        grain,
        aggregation,
        metric: props.metric,
        valid: validationMessageFor(grain, aggregation) === '',
    });
};

const updateGrain = (value: string) => {
    emit('update:grain', value);
    emitChange(value, props.aggregation);
};

const updateAggregation = (value: string) => {
    emit('update:aggregation', value);
    emitChange(props.grain, value);
};
</script>

<template>
    <section class="rounded-2xl border bg-card p-5 text-card-foreground shadow-sm" aria-labelledby="analytics-aggregation-title">
        <div class="space-y-2">
            <h2 id="analytics-aggregation-title" class="text-base font-semibold text-foreground">
                {{ props.title }}
            </h2>
            <p class="max-w-2xl text-sm leading-6 text-muted-foreground">
                {{ props.description }}
            </p>
            <p class="inline-flex rounded-full border bg-muted/60 px-3 py-1 text-xs font-medium text-muted-foreground">
                Contract: {{ selectedContractLabel }}
            </p>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
                <Label for="analytics-grain-selector">{{ props.grainLabel }}</Label>
                <select
                    id="analytics-grain-selector"
                    :value="props.grain"
                    :disabled="props.disabled"
                    :aria-invalid="!isValid"
                    aria-describedby="analytics-aggregation-help analytics-aggregation-error"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs transition outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50"
                    @change="updateGrain(($event.target as HTMLSelectElement).value)"
                >
                    <option value="">Select grain</option>
                    <option
                        v-for="option in props.grainOptions"
                        :key="`grain-${option.value}`"
                        :value="option.value"
                        :disabled="!isSupported(option)"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <p v-if="selectedGrain?.description" class="text-xs leading-5 text-muted-foreground">
                    {{ selectedGrain.description }}
                </p>
            </div>

            <div class="space-y-2">
                <Label for="analytics-aggregation-selector">{{ props.aggregationLabel }}</Label>
                <select
                    id="analytics-aggregation-selector"
                    :value="props.aggregation"
                    :disabled="props.disabled"
                    :aria-invalid="!isValid"
                    aria-describedby="analytics-aggregation-help analytics-aggregation-error"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs transition outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50"
                    @change="updateAggregation(($event.target as HTMLSelectElement).value)"
                >
                    <option value="">Select aggregation</option>
                    <option
                        v-for="option in props.aggregationOptions"
                        :key="`aggregation-${option.value}`"
                        :value="option.value"
                        :disabled="!isSupported(option)"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <p v-if="selectedAggregation?.description" class="text-xs leading-5 text-muted-foreground">
                    {{ selectedAggregation.description }}
                </p>
            </div>
        </div>

        <div class="mt-4 space-y-2">
            <p id="analytics-aggregation-help" class="text-xs leading-5 text-muted-foreground">
                Grain and aggregation stay visible so users understand the resolution behind every analytical value.
            </p>
            <p v-if="validationMessage" id="analytics-aggregation-error" class="text-sm font-medium text-destructive" role="alert">
                {{ validationMessage }}
            </p>
        </div>
    </section>
</template>
