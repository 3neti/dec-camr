<script setup lang="ts">
import { computed } from 'vue';
import { Label } from '@/components/ui/label';

type BuildingOption = {
    value: string;
    label: string;
    description: string;
};

type MeterOption = {
    value: string;
    label: string;
    description: string;
    buildingCode: string;
};

const props = withDefaults(
    defineProps<{
        buildingCode: string;
        meterIdentifier: string;
        buildingOptions: BuildingOption[];
        meterOptions: MeterOption[];
        title?: string;
        description?: string;
        disabled?: boolean;
    }>(),
    {
        title: 'Analysis Context',
        description: 'Choose the building and meter whose telemetry should drive this investigation.',
        disabled: false,
    },
);

const emit = defineEmits<{
    change: [payload: { buildingCode: string; meterIdentifier: string; valid: boolean }];
}>();

const selectedBuilding = computed(() => props.buildingOptions.find((option) => option.value === props.buildingCode));
const selectedMeter = computed(() => props.meterOptions.find((option) => option.value === props.meterIdentifier));
const validationMessage = computed(() => {
    if (!props.buildingCode || !selectedBuilding.value) {
        return 'Choose a building with available analytics telemetry.';
    }

    if (!props.meterIdentifier || !selectedMeter.value) {
        return 'Choose a meter with available analytics telemetry.';
    }

    return '';
});
const isValid = computed(() => validationMessage.value === '');

const updateBuilding = (buildingCode: string) => {
    emit('change', {
        buildingCode,
        meterIdentifier: '',
        valid: buildingCode !== '',
    });
};

const updateMeter = (meterIdentifier: string) => {
    emit('change', {
        buildingCode: props.buildingCode,
        meterIdentifier,
        valid: props.buildingCode !== '' && meterIdentifier !== '',
    });
};
</script>

<template>
    <section class="rounded-2xl border bg-card p-5 text-card-foreground shadow-sm" aria-labelledby="analytics-context-selector-title">
        <div class="space-y-2">
            <h2 id="analytics-context-selector-title" class="text-base font-semibold text-foreground">
                {{ props.title }}
            </h2>
            <p class="max-w-2xl text-sm leading-6 text-muted-foreground">
                {{ props.description }}
            </p>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
                <Label for="analytics-building-selector">Building</Label>
                <select
                    id="analytics-building-selector"
                    :value="props.buildingCode"
                    :disabled="props.disabled"
                    :aria-invalid="!isValid"
                    aria-describedby="analytics-context-selector-help analytics-context-selector-error"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs transition outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50"
                    @change="updateBuilding(($event.target as HTMLSelectElement).value)"
                >
                    <option value="">Select building</option>
                    <option
                        v-for="option in props.buildingOptions"
                        :key="`building-${option.value}`"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <p v-if="selectedBuilding?.description" class="text-xs leading-5 text-muted-foreground">
                    {{ selectedBuilding.description }}
                </p>
            </div>

            <div class="space-y-2">
                <Label for="analytics-meter-selector">Meter</Label>
                <select
                    id="analytics-meter-selector"
                    :value="props.meterIdentifier"
                    :disabled="props.disabled || props.meterOptions.length === 0"
                    :aria-invalid="!isValid"
                    aria-describedby="analytics-context-selector-help analytics-context-selector-error"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs transition outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50"
                    @change="updateMeter(($event.target as HTMLSelectElement).value)"
                >
                    <option value="">Select meter</option>
                    <option
                        v-for="option in props.meterOptions"
                        :key="`meter-${option.value}`"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <p v-if="selectedMeter?.description" class="text-xs leading-5 text-muted-foreground">
                    {{ selectedMeter.description }}
                </p>
            </div>
        </div>

        <div class="mt-4 space-y-2">
            <p id="analytics-context-selector-help" class="text-xs leading-5 text-muted-foreground">
                Context selection changes the telemetry source. The same selected time window remains visible for comparison.
            </p>
            <p v-if="validationMessage" id="analytics-context-selector-error" class="text-sm font-medium text-destructive" role="alert">
                {{ validationMessage }}
            </p>
        </div>
    </section>
</template>
