<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { site } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Site',
                href: site(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <h1 class="text-xl font-semibold">Site Dashboard</h1>

        <p class="text-sm text-muted-foreground">
            Welcome to CAMR site management.
        </p>

        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <section class="rounded-xl border border-sidebar-border/70 p-4">
                <h2 class="font-medium">Site</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    View and manage site-level context.
                </p>
            </section>
            <section class="rounded-xl border border-sidebar-border/70 p-4">
                <h2 class="font-medium">Gateway</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    Track gateway connections and operational state.
                </p>
            </section>
            <section class="rounded-xl border border-sidebar-border/70 p-4">
                <h2 class="font-medium">Meter</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    Navigate meter inventories and location details.
                </p>
            </section>
        </div>

        <section class="rounded-xl border border-sidebar-border/70 p-4">
            <h2 class="font-medium">Building</h2>
            <p class="mt-2 text-sm text-muted-foreground">
                Open building and energy-room context from this dashboard.
            </p>
        </section>
        <section class="rounded-xl border border-sidebar-border/70 p-4">
            <h2 class="font-medium">User Access</h2>
            <p class="mt-2 text-sm text-muted-foreground">
                Update site access and role scope for operators.
            </p>
        </section>
    </div>
</template>
