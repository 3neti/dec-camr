<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ValidationSummary from '@/components/operator/ValidationSummary.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type EntityField = {
    id: string;
    name: string;
    label?: string;
    type?: string;
    placeholder?: string;
    value?: string | number | boolean | null;
    hidden?: boolean;
};

const props = withDefaults(
    defineProps<{
        title: string;
        description?: string;
        action: string;
        method?: string;
        submitLabel?: string;
        fields: EntityField[];
        csrfToken?: string;
        gridClass?: string;
        submitWrapperClass?: string;
    }>(),
    {
        method: 'POST',
        submitLabel: 'Create',
        description: '',
        gridClass: 'grid gap-4',
        submitWrapperClass: 'flex items-end',
    },
);

type ValidationErrorValue = string | string[] | undefined;

const page = usePage();

const pageErrors = computed<Record<string, ValidationErrorValue>>(() => {
    const errors = page.props.errors;

    if (!errors || typeof errors !== 'object' || Array.isArray(errors)) {
        return {};
    }

    return errors as Record<string, ValidationErrorValue>;
});

const normalizeErrors = (error: ValidationErrorValue): string[] => {
    if (Array.isArray(error)) {
        return error.filter((message): message is string => typeof message === 'string' && message.length > 0);
    }

    if (typeof error === 'string' && error.length > 0) {
        return [error];
    }

    return [];
};

const fieldError = (fieldName: string): string | undefined => normalizeErrors(pageErrors.value[fieldName])[0];

const summaryErrors = computed(() =>
    Array.from(
        new Set(
            props.fields.flatMap((field) => normalizeErrors(pageErrors.value[field.name])),
        ),
    ),
);
</script>

<template>
    <Card>
        <CardHeader class="space-y-1">
            <CardTitle>{{ props.title }}</CardTitle>
            <CardDescription>{{ props.description }}</CardDescription>
        </CardHeader>
        <CardContent>
            <form :method="props.method" :action="props.action" :class="props.gridClass">
                <input v-if="props.csrfToken" type="hidden" name="_token" :value="props.csrfToken" />
                <ValidationSummary :errors="summaryErrors" />
                <template v-for="field in props.fields" :key="field.name">
                    <input
                        v-if="field.hidden"
                        type="hidden"
                        :id="field.id"
                        :name="field.name"
                        :value="field.value ?? ''"
                    />
                    <div v-else class="space-y-2">
                        <Label :for="field.id">{{ field.label }}</Label>
                        <Input
                            :id="field.id"
                            :type="field.type ?? 'text'"
                            :name="field.name"
                            :placeholder="field.placeholder"
                            :aria-invalid="fieldError(field.name) ? 'true' : undefined"
                            :aria-describedby="fieldError(field.name) ? `${field.id}-error` : undefined"
                        />
                        <p
                            v-if="fieldError(field.name)"
                            :id="`${field.id}-error`"
                            class="text-sm font-medium text-destructive"
                        >
                            {{ fieldError(field.name) }}
                        </p>
                    </div>
                </template>
                <slot />
                <div :class="props.submitWrapperClass">
                    <Button type="submit">{{ props.submitLabel }}</Button>
                </div>
            </form>
        </CardContent>
    </Card>
</template>
