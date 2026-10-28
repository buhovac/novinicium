<script setup lang="ts">
import {
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    DoughnutController,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PieController,
    PointElement,
    Tooltip,
} from 'chart.js';
import type { ChartConfiguration, ChartDataset } from 'chart.js';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import type { ChartNode } from '@/types/blog-content';

/**
 * The Chart.js drawing for ArticleChart (loaded lazily, client only). It is
 * a visual aid: ArticleChart hides it from assistive tech because the data
 * table next to it holds the same numbers.
 *
 * Series never depend on colour alone: bars and slices also differ by
 * pattern, lines by dash style and point shape, and every graphic edge is
 * dark grey on white (≥ 3:1). Lime is only ever a fill behind a pattern.
 */
Chart.register(
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    DoughnutController,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PieController,
    PointElement,
    Tooltip,
);

const props = defineProps<{
    node: ChartNode;
}>();

const canvas = ref<HTMLCanvasElement | null>(null);
let chart: Chart | null = null;

type PatternKind = 'none' | 'stripes' | 'dots' | 'crosshatch';

function brandColour(token: string, fallback: string): string {
    const value = getComputedStyle(document.documentElement)
        .getPropertyValue(token)
        .trim();

    return value || fallback;
}

function patternFill(
    background: string,
    foreground: string,
    kind: PatternKind,
): string | CanvasPattern {
    const tile = document.createElement('canvas');
    tile.width = 10;
    tile.height = 10;
    const context = tile.getContext('2d');

    if (kind === 'none' || context === null) {
        return background;
    }

    context.fillStyle = background;
    context.fillRect(0, 0, 10, 10);
    context.strokeStyle = foreground;
    context.fillStyle = foreground;
    context.lineWidth = 2;

    if (kind === 'dots') {
        context.beginPath();
        context.arc(5, 5, 1.6, 0, Math.PI * 2);
        context.fill();
    } else {
        context.beginPath();
        context.moveTo(0, 10);
        context.lineTo(10, 0);

        if (kind === 'crosshatch') {
            context.moveTo(0, 0);
            context.lineTo(10, 10);
        }

        context.stroke();
    }

    return context.createPattern(tile, 'repeat') ?? background;
}

/** Wrap long category names onto lines instead of drawing them slanted. */
function wrapLabel(label: string, maxLineLength = 18): string[] {
    const lines: string[] = [];

    for (const word of label.split(' ')) {
        const current = lines.at(-1);

        if (
            current !== undefined &&
            `${current} ${word}`.length <= maxLineLength
        ) {
            lines[lines.length - 1] = `${current} ${word}`;
        } else {
            lines.push(word);
        }
    }

    return lines;
}

function buildConfiguration(node: ChartNode): ChartConfiguration {
    const gray = brandColour('--ni-gray', '#353535');
    const grayLight = brandColour('--ni-gray-light', '#5a5a5a');
    const grayLighter = brandColour('--ni-gray-lighter', '#8a8a8a');
    const grayLightest = brandColour('--ni-gray-lightest', '#e5e5e5');
    const green = brandColour('--ni-green', '#beef35');
    const greenLight = brandColour('--ni-green-light', '#d4f07e');
    const white = brandColour('--ni-white', '#ffffff');

    /** Fills for bars (per series) and slices (per category). */
    const fills: [string, PatternKind][] = [
        [gray, 'none'],
        [green, 'stripes'],
        [grayLighter, 'dots'],
        [greenLight, 'crosshatch'],
        [grayLight, 'none'],
        [grayLightest, 'stripes'],
    ];
    const fill = (index: number): string | CanvasPattern => {
        const [background, kind] = fills[index % fills.length];

        return patternFill(background, gray, kind);
    };

    /** Lines: all ≥ 3:1 on white, told apart by dash and point shape. */
    const lineStyles = [
        { colour: gray, dash: [], point: 'circle' },
        { colour: grayLight, dash: [8, 4], point: 'rect' },
        { colour: grayLighter, dash: [2, 3], point: 'triangle' },
    ] as const;

    const prefersReducedMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;
    const isCircular =
        node.chartType === 'pie' || node.chartType === 'doughnut';

    const datasets = node.series.map((series, index) => {
        if (isCircular) {
            return {
                label: series.name,
                data: series.values,
                backgroundColor: series.values.map((_, slice) => fill(slice)),
                borderColor: white,
                borderWidth: 2,
            };
        }

        if (node.chartType === 'line') {
            const style = lineStyles[index % lineStyles.length];

            return {
                label: series.name,
                data: series.values,
                borderColor: style.colour,
                backgroundColor: style.colour,
                borderDash: [...style.dash],
                borderWidth: 3,
                pointStyle: style.point,
                pointRadius: 5,
            };
        }

        return {
            label: series.name,
            data: series.values,
            backgroundColor: fill(index),
            borderColor: gray,
            borderWidth: 2,
        };
    }) as ChartDataset[];

    return {
        type: node.chartType,
        data: {
            labels: isCircular
                ? node.categories
                : node.categories.map((category) => wrapLabel(category)),
            datasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: prefersReducedMotion ? false : { duration: 400 },
            color: gray,
            plugins: {
                legend: {
                    display: isCircular || node.series.length > 1,
                    position: 'bottom',
                    labels: {
                        color: gray,
                        usePointStyle: node.chartType === 'line',
                    },
                },
                tooltip: {
                    callbacks: {
                        label: (item) => {
                            const series = node.series[item.datasetIndex];
                            const value = series?.display[item.dataIndex] ?? '';
                            const name = isCircular
                                ? node.categories[item.dataIndex]
                                : series?.name;

                            return `${name}: ${value}`;
                        },
                    },
                },
            },
            scales: isCircular
                ? {}
                : {
                      x: {
                          ticks: {
                              color: gray,
                              maxRotation: 0,
                              autoSkip: false,
                          },
                          grid: { display: false },
                      },
                      y: {
                          beginAtZero: true,
                          ticks: { color: gray },
                          grid: { color: grayLightest },
                          title: {
                              display: node.unit !== null,
                              text: node.unit ?? '',
                              color: gray,
                          },
                      },
                  },
        },
    } as ChartConfiguration;
}

function draw(): void {
    chart?.destroy();
    chart = null;

    if (canvas.value !== null) {
        Chart.defaults.font.family = getComputedStyle(canvas.value).fontFamily;
        chart = new Chart(canvas.value, buildConfiguration(props.node));
    }
}

onMounted(draw);
// Redraw if the same component instance receives another chart's data.
watch(() => props.node, draw, { deep: true });
onBeforeUnmount(() => chart?.destroy());
</script>

<template>
    <canvas ref="canvas"></canvas>
</template>
