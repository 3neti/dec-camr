<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

type BreadcrumbItem = {
    label: string;
    href?: string;
};

const props = defineProps<{
    items: BreadcrumbItem[];
}>();
</script>

<template>
    <nav aria-label="Operator hierarchy" class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-[0.24em] text-muted-foreground">
        <template v-for="(item, index) in props.items" :key="`${item.label}-${index}`">
            <Link v-if="item.href" :href="item.href" class="transition hover:text-foreground">
                {{ item.label }}
            </Link>
            <span v-else class="text-foreground">{{ item.label }}</span>
            <span v-if="index < props.items.length - 1" aria-hidden="true" class="text-border">/</span>
        </template>
    </nav>
</template>
