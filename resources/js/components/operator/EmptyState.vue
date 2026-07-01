<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
    }>(),
    {
        title: 'No records',
        description: 'No records are available for this view.',
    },
);
</script>

<template>
    <div class="rounded-md border border-dashed p-8 text-center text-sm text-muted-foreground">
        <p class="font-medium text-foreground">{{ props.title }}</p>
        <p>{{ props.description }}</p>
    </div>
</template>
