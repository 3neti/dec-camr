<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        orientation?: 'row' | 'column';
    }>(),
    {
        orientation: 'row',
    },
);
</script>

<template>
    <div
        :class="
            props.orientation === 'column'
                ? 'flex flex-col items-start gap-2'
                : 'flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end'
        "
    >
        <slot />
    </div>
</template>
