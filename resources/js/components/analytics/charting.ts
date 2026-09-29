import type { EChartsOption } from 'echarts';

export const chartTextColor = '#64748b';
export const chartGridLineColor = 'rgba(148, 163, 184, 0.22)';

export const baseGrid = {
    top: 28,
    right: 18,
    bottom: 38,
    left: 48,
    containLabel: true,
};

export const baseTooltip = {
    trigger: 'axis',
    backgroundColor: 'rgba(15, 23, 42, 0.94)',
    borderWidth: 0,
    textStyle: {
        color: '#f8fafc',
        fontSize: 12,
    },
    extraCssText: 'border-radius: 14px; box-shadow: 0 18px 45px rgba(15, 23, 42, 0.22);',
} satisfies EChartsOption['tooltip'];

export const categoryAxis = (data: string[], name?: string): EChartsOption['xAxis'] => ({
    type: 'category',
    boundaryGap: false,
    data,
    name,
    nameTextStyle: {
        color: chartTextColor,
    },
    axisLabel: {
        color: chartTextColor,
        hideOverlap: true,
    },
    axisLine: {
        lineStyle: {
            color: chartGridLineColor,
        },
    },
    axisTick: {
        show: false,
    },
});

export const valueAxis = (name: string): EChartsOption['yAxis'] => ({
    type: 'value',
    name,
    nameTextStyle: {
        color: chartTextColor,
    },
    axisLabel: {
        color: chartTextColor,
    },
    splitLine: {
        lineStyle: {
            color: chartGridLineColor,
        },
    },
});

export const formatChartNumber = (value: number | null | undefined, maximumFractionDigits = 2): string => {
    if (value === null || value === undefined) {
        return 'Unavailable';
    }

    return new Intl.NumberFormat(undefined, { maximumFractionDigits }).format(value);
};

export const formatChartLabel = (value: string): string => new Intl.DateTimeFormat(undefined, {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
}).format(new Date(value));
