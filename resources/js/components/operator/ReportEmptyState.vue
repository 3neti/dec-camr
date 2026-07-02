<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import EmptyState from '@/components/operator/EmptyState.vue';
import ScopePill from '@/components/operator/ScopePill.vue';

type EmptyAction = {
    id: string;
    label: string;
    description: string;
    href: string;
};

const props = defineProps<{
    title: string;
    description: string;
    scopeLabel: string;
    rangeLabel: string;
    nextActions: EmptyAction[];
}>();
</script>

<template>
    <div class="space-y-4">
        <EmptyState :title="props.title" :description="props.description" />

        <dl class="grid gap-3 md:grid-cols-2">
            <div class="rounded-xl border bg-muted/20 p-4">
                <dt class="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground">
                    Current scope
                </dt>
                <dd class="mt-3">
                    <ScopePill label="Scope" :value="props.scopeLabel" tone="info" />
                </dd>
            </div>

            <div class="rounded-xl border bg-muted/20 p-4">
                <dt class="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground">
                    Current range
                </dt>
                <dd class="mt-2 text-sm font-semibold leading-6 text-foreground">
                    {{ props.rangeLabel }}
                </dd>
            </div>
        </dl>

        <div class="space-y-3">
            <h3 class="text-sm font-semibold text-foreground">Next actions</h3>

            <div class="grid gap-3">
                <template v-for="action in props.nextActions" :key="action.id">
                    <a
                        v-if="action.href.startsWith('#')"
                        :href="action.href"
                        class="rounded-xl border bg-background p-4 text-left transition hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <p class="text-sm font-semibold text-foreground">
                            {{ action.label }}
                        </p>
                        <p class="mt-1 text-sm leading-6 text-muted-foreground">
                            {{ action.description }}
                        </p>
                    </a>

                    <Link
                        v-else
                        :href="action.href"
                        class="rounded-xl border bg-background p-4 text-left transition hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <p class="text-sm font-semibold text-foreground">
                            {{ action.label }}
                        </p>
                        <p class="mt-1 text-sm leading-6 text-muted-foreground">
                            {{ action.description }}
                        </p>
                    </Link>
                </template>
            </div>
        </div>
    </div>
</template>
