<script setup lang="ts">
import type { EChartsOption } from 'echarts';
import { BarChart, LineChart } from 'echarts/charts';
import { GridComponent, LegendComponent, MarkPointComponent, TooltipComponent } from 'echarts/components';
import { use } from 'echarts/core';
import { SVGRenderer } from 'echarts/renderers';
import VChart from 'vue-echarts';

use([
    SVGRenderer,
    LineChart,
    BarChart,
    GridComponent,
    TooltipComponent,
    LegendComponent,
    MarkPointComponent,
]);

const props = withDefaults(
    defineProps<{
        option: EChartsOption;
        ariaLabel: string;
        height?: number;
        testId?: string;
    }>(),
    {
        height: 280,
        testId: 'analytics-chart',
    },
);
</script>

<template>
    <div :data-test="props.testId" class="rounded-2xl border bg-background/70 p-3">
        <VChart
            :option="props.option"
            :style="{ height: `${props.height}px`, width: '100%' }"
            autoresize
            role="img"
            :aria-label="props.ariaLabel"
        />
    </div>
</template>
