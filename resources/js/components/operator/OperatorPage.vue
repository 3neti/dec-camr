<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    title: string;
    heading?: string;
    description?: string;
}>();
</script>

<template>
    <Head :title="props.title" />

    <div class="space-y-6">
        <h1 class="text-2xl font-semibold">{{ props.heading ?? props.title }}</h1>
        <p v-if="props.description" class="text-sm text-muted-foreground">{{ props.description }}</p>

        <slot />
    </div>
</template>
