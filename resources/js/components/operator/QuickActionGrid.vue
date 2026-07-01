<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type { RouteDefinition } from '@/wayfinder';

type QuickActionItem = {
    id: string;
    title: string;
    description: string;
    href: string | RouteDefinition<'get'>;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        items: QuickActionItem[];
    }>(),
    {
        title: 'Quick Actions',
        description: 'Preserved workflows routed into existing maintenance and reporting surfaces.',
    },
);
</script>

<template>
    <Card class="py-5">
        <CardHeader class="px-5 pb-0">
            <CardTitle>{{ props.title }}</CardTitle>
            <CardDescription>{{ props.description }}</CardDescription>
        </CardHeader>

        <CardContent class="grid gap-3 px-5 sm:grid-cols-2">
            <Link
                v-for="item in props.items"
                :key="item.id"
                :href="item.href"
                class="group rounded-xl border bg-background p-4 text-left transition hover:bg-muted"
            >
                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-foreground">
                            {{ item.title }}
                        </p>
                        <span class="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground transition group-hover:text-foreground">
                            Open
                        </span>
                    </div>

                    <p class="text-sm leading-6 text-muted-foreground">
                        {{ item.description }}
                    </p>
                </div>
            </Link>
        </CardContent>
    </Card>
</template>
