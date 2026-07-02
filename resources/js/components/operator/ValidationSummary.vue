<script setup lang="ts">
import { AlertCircle } from '@lucide/vue';
import { computed } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

const props = withDefaults(
    defineProps<{
        errors: string[];
        title?: string;
    }>(),
    {
        title: 'Please correct the following fields.',
    },
);

const uniqueErrors = computed(() => Array.from(new Set(props.errors.filter((error) => error.length > 0))));
</script>

<template>
    <Alert v-if="uniqueErrors.length > 0" variant="destructive" aria-live="assertive" aria-atomic="true">
        <AlertCircle class="size-4" />
        <AlertTitle>{{ props.title }}</AlertTitle>
        <AlertDescription>
            <ul class="list-inside list-disc space-y-1 text-sm">
                <li v-for="(error, index) in uniqueErrors" :key="index">
                    {{ error }}
                </li>
            </ul>
        </AlertDescription>
    </Alert>
</template>
