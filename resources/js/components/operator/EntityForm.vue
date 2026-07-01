<script setup lang="ts">
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
                        />
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
