<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type TimeRangePreset = {
    key: string;
    label: string;
    from: string;
    to: string;
    description?: string;
};

const props = withDefaults(
    defineProps<{
        from: string;
        to: string;
        timezoneLabel: string;
        min?: string;
        max?: string;
        title?: string;
        description?: string;
        fromLabel?: string;
        toLabel?: string;
        presets?: TimeRangePreset[];
        disabled?: boolean;
    }>(),
    {
        min: '',
        max: '',
        title: 'Time Range',
        description: 'Frame the analysis window before reviewing trends or summaries.',
        fromLabel: 'From',
        toLabel: 'To',
        presets: () => [],
        disabled: false,
    },
);

const emit = defineEmits<{
    'update:from': [value: string];
    'update:to': [value: string];
    change: [payload: { from: string; to: string; timezoneLabel: string; valid: boolean }];
    preset: [preset: TimeRangePreset];
}>();

const validationMessageFor = (from: string, to: string) => {
    if (!from || !to) {
        return 'Choose both start and end dates to define an analytics window.';
    }

    if (from > to) {
        return 'Start date must be on or before end date.';
    }

    if (props.min && from < props.min) {
        return `Start date must be on or after ${props.min}.`;
    }

    if (props.max && to > props.max) {
        return `End date must be on or before ${props.max}.`;
    }

    return '';
};

const validationMessage = computed(() => validationMessageFor(props.from, props.to));
const isValid = computed(() => validationMessage.value === '');
const selectedPresetKey = computed(() => props.presets.find((preset) => preset.from === props.from && preset.to === props.to)?.key ?? 'custom');

const emitChange = (from: string, to: string) => {
    emit('change', {
        from,
        to,
        timezoneLabel: props.timezoneLabel,
        valid: validationMessageFor(from, to) === '',
    });
};

const updateFrom = (value: string | number) => {
    const from = String(value);

    emit('update:from', from);
    emitChange(from, props.to);
};

const updateTo = (value: string | number) => {
    const to = String(value);

    emit('update:to', to);
    emitChange(props.from, to);
};

const applyPreset = (preset: TimeRangePreset) => {
    if (props.disabled) {
        return;
    }

    emit('update:from', preset.from);
    emit('update:to', preset.to);
    emit('preset', preset);
    emitChange(preset.from, preset.to);
};
</script>

<template>
    <section class="rounded-2xl border bg-card p-5 text-card-foreground shadow-sm" aria-labelledby="analytics-time-range-title">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <div class="space-y-1">
                    <h2 id="analytics-time-range-title" class="text-base font-semibold text-foreground">
                        {{ props.title }}
                    </h2>
                    <p class="max-w-2xl text-sm leading-6 text-muted-foreground">
                        {{ props.description }}
                    </p>
                </div>

                <p class="inline-flex rounded-full border bg-muted/60 px-3 py-1 text-xs font-medium text-muted-foreground">
                    Timezone: {{ props.timezoneLabel }}
                </p>
            </div>

            <div v-if="props.presets.length > 0" class="flex flex-wrap gap-2" aria-label="Time range presets">
                <Button
                    v-for="preset in props.presets"
                    :key="preset.key"
                    type="button"
                    size="sm"
                    :variant="selectedPresetKey === preset.key ? 'default' : 'outline'"
                    :disabled="props.disabled"
                    :aria-pressed="selectedPresetKey === preset.key"
                    @click="applyPreset(preset)"
                >
                    {{ preset.label }}
                </Button>
            </div>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
                <Label for="analytics-time-range-from">{{ props.fromLabel }}</Label>
                <Input
                    id="analytics-time-range-from"
                    :model-value="props.from"
                    type="date"
                    :min="props.min || undefined"
                    :max="props.max || undefined"
                    :disabled="props.disabled"
                    :aria-invalid="!isValid"
                    aria-describedby="analytics-time-range-help analytics-time-range-error"
                    @update:model-value="updateFrom"
                />
            </div>

            <div class="space-y-2">
                <Label for="analytics-time-range-to">{{ props.toLabel }}</Label>
                <Input
                    id="analytics-time-range-to"
                    :model-value="props.to"
                    type="date"
                    :min="props.min || undefined"
                    :max="props.max || undefined"
                    :disabled="props.disabled"
                    :aria-invalid="!isValid"
                    aria-describedby="analytics-time-range-help analytics-time-range-error"
                    @update:model-value="updateTo"
                />
            </div>
        </div>

        <div class="mt-4 space-y-2">
            <p id="analytics-time-range-help" class="text-xs leading-5 text-muted-foreground">
                Selected range is preserved as date boundaries. Later analytics contracts decide grain and aggregation.
            </p>
            <p v-if="validationMessage" id="analytics-time-range-error" class="text-sm font-medium text-destructive" role="alert">
                {{ validationMessage }}
            </p>
        </div>
    </section>
</template>
