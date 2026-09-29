<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

type DrilldownAction = {
    label: string;
    href: string;
    primary?: boolean;
};

const props = defineProps<{
    actions: DrilldownAction[];
}>();
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <Link
            v-for="action in props.actions"
            :key="`${action.label}-${action.href}`"
            :href="action.href"
            :class="[
                'inline-flex items-center justify-center rounded-full px-4 py-2 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2',
                action.primary
                    ? 'bg-emerald-600 text-white shadow-sm hover:bg-emerald-700'
                    : 'border border-border bg-background text-foreground hover:border-emerald-300 hover:text-emerald-700 dark:hover:text-emerald-300',
            ]"
        >
            {{ action.label }}
        </Link>
    </div>
</template>
