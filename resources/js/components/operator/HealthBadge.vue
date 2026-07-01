<script setup lang="ts">
import StatusChip from './StatusChip.vue';

const props = withDefaults(
    defineProps<{
        healthy?: boolean;
        label?: string;
        unhealthyLabel?: string;
    }>(),
    {
        healthy: false,
        label: 'Online',
        unhealthyLabel: 'Offline',
    },
);
</script>

<template>
    <StatusChip
        :label="props.healthy ? props.label : props.unhealthyLabel"
        :tone="props.healthy ? 'success' : 'danger'"
    />
</template>
