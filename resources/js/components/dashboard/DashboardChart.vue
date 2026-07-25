<script setup lang="ts">
import type { ApexOptions } from 'apexcharts';
import { computed, defineAsyncComponent, onMounted, ref } from 'vue';
import { useAppearance } from '@/composables/useAppearance';

type ChartSeries = Array<{
    name: string;
    data: number[];
}>;

const props = withDefaults(
    defineProps<{
        title: string;
        description: string;
        type: 'bar' | 'line' | 'donut';
        series: ChartSeries | number[];
        categories?: string[];
        labels?: string[];
        colors: string[];
        totalLabel?: string;
        stacked?: boolean;
        horizontal?: boolean;
    }>(),
    {
        categories: () => [],
        labels: () => [],
        totalLabel: 'Total',
        stacked: false,
        horizontal: false,
    },
);

const ApexChart = defineAsyncComponent(() => import('vue3-apexcharts'));
const mounted = ref(false);
const { resolvedAppearance } = useAppearance();

onMounted(() => {
    mounted.value = true;
});

const options = computed<ApexOptions>(() => {
    const dark = resolvedAppearance.value === 'dark';
    const textColor = dark ? '#a1a1aa' : '#52525b';
    const gridColor = dark ? '#27272a' : '#e4e4e7';

    return {
        chart: {
            background: 'transparent',
            fontFamily: 'inherit',
            foreColor: textColor,
            stacked: props.stacked,
            toolbar: { show: false },
            animations: {
                enabled: true,
                speed: 300,
                animateGradually: { enabled: false },
                dynamicAnimation: { enabled: false },
            },
        },
        colors: props.colors,
        dataLabels: { enabled: false },
        grid: {
            borderColor: gridColor,
            strokeDashArray: 3,
            padding: { left: 4, right: 8 },
        },
        labels: props.labels,
        legend: {
            position: 'bottom',
            fontSize: '12px',
            labels: { colors: textColor },
            markers: { size: 5 },
            itemMargin: { horizontal: 10, vertical: 4 },
        },
        noData: {
            text: 'No data for the active period',
            align: 'center',
            verticalAlign: 'middle',
            style: { color: textColor, fontSize: '13px' },
        },
        plotOptions: {
            bar: {
                horizontal: props.horizontal,
                borderRadius: 3,
                borderRadiusApplication: 'end',
                columnWidth: '58%',
                barHeight: '62%',
            },
            pie: {
                donut: {
                    size: '68%',
                    labels: {
                        show: true,
                        name: { show: true },
                        value: {
                            show: true,
                            fontSize: '22px',
                            fontWeight: 600,
                            color: dark ? '#fafafa' : '#18181b',
                        },
                        total: {
                            show: true,
                            label: props.totalLabel,
                            color: textColor,
                            formatter: (chart) =>
                                String(
                                    chart.globals.seriesTotals.reduce(
                                        (total: number, value: number) =>
                                            total + value,
                                        0,
                                    ),
                                ),
                        },
                    },
                },
            },
        },
        stroke: {
            curve: 'smooth',
            width: props.type === 'line' ? 3 : 0,
        },
        theme: { mode: dark ? 'dark' : 'light' },
        tooltip: { theme: dark ? 'dark' : 'light' },
        xaxis: {
            categories: props.categories,
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                hideOverlappingLabels: true,
                trim: true,
                style: { colors: textColor, fontSize: '11px' },
            },
        },
        yaxis:
            props.type === 'donut'
                ? undefined
                : {
                      min: 0,
                      forceNiceScale: true,
                      labels: {
                          formatter: (value: number) =>
                              Number.isInteger(value) ? String(value) : '',
                          style: { colors: [textColor], fontSize: '11px' },
                      },
                  },
        responsive: [
            {
                breakpoint: 640,
                options: {
                    chart: { height: 280 },
                    legend: { position: 'bottom' },
                },
            },
        ],
    };
});

const accessibleSummary = computed(() => {
    if (props.type === 'donut') {
        return props.labels.map((label, index) => ({
            label,
            value: (props.series as number[])[index] ?? 0,
        }));
    }

    return (props.series as ChartSeries).map((series) => ({
        label: series.name,
        value: series.data.reduce((total, value) => total + value, 0),
    }));
});
</script>

<template>
    <section class="min-w-0 border-t pt-4" :aria-label="title">
        <div class="mb-2">
            <h3 class="text-sm font-semibold">{{ title }}</h3>
            <p class="text-xs text-muted-foreground">{{ description }}</p>
        </div>

        <div class="h-[300px] w-full">
            <ApexChart
                v-if="mounted"
                :type="type"
                height="300"
                width="100%"
                :options="options"
                :series="series"
            />
            <div
                v-else
                class="flex h-full items-center justify-center text-sm text-muted-foreground"
            >
                Loading chart
            </div>
        </div>

        <ul class="sr-only">
            <li v-for="item in accessibleSummary" :key="item.label">
                {{ item.label }}: {{ item.value }}
            </li>
        </ul>
    </section>
</template>
