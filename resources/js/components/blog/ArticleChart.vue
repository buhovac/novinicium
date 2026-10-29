<script setup lang="ts">
import { defineAsyncComponent, onMounted, ref, useId } from 'vue';
import type { ChartNode } from '@/types/blog-content';

/**
 * Chart block. The data table is the content: it is server-rendered, works
 * without JavaScript and is always visible, captioned with the chart title,
 * with scoped row/column headers and values formatted with their unit.
 *
 * The drawn chart is a supplementary visual of exactly the same numbers.
 * Its wrapper is aria-hidden: a role="img" summary would repeat the table
 * in a lossier form, so assistive tech gets the table alone, once.
 *
 * Chart.js lives in ChartCanvas.vue and is only fetched (as its own chunk)
 * after mount on pages that contain a chart; the server never loads it.
 */
const ChartCanvas = defineAsyncComponent(
    () => import('@/components/blog/ChartCanvas.vue'),
);

defineProps<{
    node: ChartNode;
}>();

const captionId = useId();
const canDrawChart = ref(false);

onMounted(() => {
    canDrawChart.value = true;
});
</script>

<template>
    <figure class="ni-chart" :data-chart-type="node.chartType">
        <div v-if="canDrawChart" class="ni-chart-visual" aria-hidden="true">
            <ChartCanvas :node="node" />
        </div>

        <div
            class="ni-table"
            role="region"
            tabindex="0"
            :aria-labelledby="captionId"
        >
            <table>
                <caption :id="captionId">
                    {{
                        node.title
                    }}
                </caption>
                <thead>
                    <tr>
                        <th scope="col">{{ node.categoryHeading }}</th>
                        <th
                            v-for="(series, seriesIndex) in node.series"
                            :key="seriesIndex"
                            scope="col"
                        >
                            {{ series.name }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(category, categoryIndex) in node.categories"
                        :key="categoryIndex"
                    >
                        <th scope="row">{{ category }}</th>
                        <td
                            v-for="(series, seriesIndex) in node.series"
                            :key="seriesIndex"
                        >
                            {{ series.display[categoryIndex] }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <figcaption v-if="node.caption || node.source">
            <span v-if="node.caption">{{ node.caption }}</span>
            <span v-if="node.source" class="ni-chart-source"
                >Source: {{ node.source }}</span
            >
        </figcaption>
    </figure>
</template>
