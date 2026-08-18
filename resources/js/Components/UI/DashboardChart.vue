<script setup lang="ts">
import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    DoughnutController,
    Legend,
    LineController,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
    type ChartConfiguration,
    type ChartType,
    type TooltipItem,
} from 'chart.js';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

Chart.register(ArcElement, BarController, BarElement, CategoryScale, DoughnutController, Legend, LineController, LineElement, LinearScale, PointElement, Tooltip);

type Dataset = { label: string; data: number[]; backgroundColor: string | string[]; borderColor?: string };

const props = defineProps<{
    type: Extract<ChartType, 'line' | 'doughnut' | 'bar'>;
    labels: string[];
    datasets: Dataset[];
    description: string;
    valueFormat?: 'currency' | 'count';
}>();

const canvas = ref<HTMLCanvasElement | null>(null);
let chart: Chart | null = null;
const hasData = computed(() => props.datasets.some((dataset) => dataset.data.some((value) => value > 0)));

const createChart = () => {
    chart?.destroy();
    if (!canvas.value || !hasData.value) return;

    const isDoughnut = props.type === 'doughnut';
    const isBar = props.type === 'bar';
    const config: ChartConfiguration = {
        type: props.type,
        data: {
            labels: props.labels,
            datasets: props.datasets.map((dataset) => ({
                ...dataset,
                borderWidth: isDoughnut ? 0 : 2,
                borderRadius: isBar ? 5 : 0,
                borderSkipped: isBar ? false : undefined,
                tension: props.type === 'line' ? 0.35 : undefined,
                pointRadius: props.type === 'line' ? 3 : undefined,
                pointHoverRadius: props.type === 'line' ? 5 : undefined,
                fill: props.type === 'line' ? false : undefined,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: isBar ? 'y' : 'x',
            plugins: {
                legend: { display: !isDoughnut, position: 'top', labels: { boxWidth: 10, boxHeight: 10, color: '#625d57', font: { size: 12, weight: 600 } } },
                tooltip: { displayColors: true, padding: 10, titleFont: { weight: 600 }, callbacks: { label: (context: TooltipItem<ChartType>) => { const value = Number(context.raw); return `${context.dataset.label}: ${props.valueFormat === 'currency' ? new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value) : value}`; } } },
            },
            scales: isDoughnut ? undefined : {
                x: { grid: { display: false }, border: { display: false }, ticks: { color: '#817a72', font: { size: 11, weight: 600 }, maxRotation: 0, autoSkipPadding: 12 } },
                y: { beginAtZero: true, grid: { color: '#eeeae5' }, border: { display: false }, ticks: { color: '#817a72', font: { size: 11, weight: 600 }, precision: 0, callback: (value: string | number) => props.valueFormat === 'currency' ? new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', notation: 'compact' }).format(Number(value)) : value } },
            },
            cutout: isDoughnut ? '68%' : undefined,
        } as any,
    };

    chart = new Chart(canvas.value, config);
};

onMounted(createChart);
onBeforeUnmount(() => chart?.destroy());
watch(() => [props.type, props.labels, props.datasets], createChart, { deep: true });
</script>

<template>
    <div class="relative h-52 w-full sm:h-60" :class="{ 'sm:h-64': type === 'line' }">
        <canvas v-if="hasData" ref="canvas" role="img" :aria-label="description" />
        <div v-else class="flex h-full items-center justify-center rounded border border-dashed border-[color:var(--fe-border-strong)] bg-[color:var(--fe-surface-elevated)] px-4 text-center text-sm text-[color:var(--fe-text-secondary)]">No activity to display yet.</div>
    </div>
</template>
