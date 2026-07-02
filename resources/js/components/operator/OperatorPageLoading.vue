<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
</script>

<template>
    <div class="space-y-6" aria-hidden="true">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div
                v-for="index in 4"
                :key="`operator-page-kpi-${index}`"
                class="rounded-xl border bg-card p-5 shadow-sm"
            >
                <Skeleton class="mb-3 h-3 w-24" />
                <Skeleton class="mb-4 h-8 w-20" />
                <Skeleton class="h-3 w-32" />
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <div class="space-y-6">
                <div class="rounded-xl border bg-card p-5 shadow-sm">
                    <Skeleton class="mb-3 h-4 w-40" />
                    <Skeleton class="mb-6 h-3 w-72 max-w-full" />
                    <div class="space-y-3">
                        <Skeleton class="h-12 w-full" />
                        <Skeleton class="h-12 w-full" />
                        <Skeleton class="h-12 w-full" />
                        <Skeleton class="h-12 w-full" />
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-5 shadow-sm">
                    <Skeleton class="mb-3 h-4 w-44" />
                    <Skeleton class="mb-6 h-3 w-64 max-w-full" />
                    <div class="grid gap-3 md:grid-cols-2">
                        <Skeleton class="h-24 w-full" />
                        <Skeleton class="h-24 w-full" />
                        <Skeleton class="h-24 w-full" />
                        <Skeleton class="h-24 w-full" />
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-xl border bg-card p-5 shadow-sm">
                    <Skeleton class="mb-3 h-4 w-32" />
                    <Skeleton class="mb-6 h-3 w-56 max-w-full" />
                    <div class="space-y-3">
                        <Skeleton class="h-16 w-full" />
                        <Skeleton class="h-16 w-full" />
                        <Skeleton class="h-16 w-full" />
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-5 shadow-sm">
                    <Skeleton class="mb-3 h-4 w-36" />
                    <Skeleton class="mb-6 h-3 w-48 max-w-full" />
                    <div class="space-y-3">
                        <Skeleton class="h-10 w-full" />
                        <Skeleton class="h-10 w-full" />
                        <Skeleton class="h-10 w-full" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
