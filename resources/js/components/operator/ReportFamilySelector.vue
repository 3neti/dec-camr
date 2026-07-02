<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type { RouteDefinition } from '@/wayfinder';

type ReportFamilyItem = {
    id: string;
    label: string;
    description: string;
    href: string | RouteDefinition<'get'>;
    active?: boolean;
};

const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        items: ReportFamilyItem[];
    }>(),
    {
        title: 'Report Families',
        description: 'Legacy report names remain recognizable while the operator console groups related report work more clearly.',
    },
);
</script>

<template>
    <Card class="py-5">
        <CardHeader class="px-5 pb-0">
            <CardTitle>{{ props.title }}</CardTitle>
            <CardDescription>{{ props.description }}</CardDescription>
        </CardHeader>

        <CardContent class="grid gap-3 px-5 md:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="item in props.items"
                :key="item.id"
                :href="item.href"
                class="group rounded-xl border p-4 text-left transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                :class="
                    item.active
                        ? 'border-emerald-600/60 bg-emerald-600/5 shadow-sm'
                        : 'border-border bg-background hover:bg-muted'
                "
            >
                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-foreground">
                            {{ item.label }}
                        </p>
                        <span
                            class="text-[11px] font-medium uppercase tracking-[0.18em]"
                            :class="item.active ? 'text-emerald-700' : 'text-muted-foreground transition group-hover:text-foreground'"
                        >
                            {{ item.active ? 'Current' : 'Open' }}
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
