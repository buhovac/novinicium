<script setup lang="ts">
import { computed, useId } from 'vue';
import ContentNode from '@/components/blog/ContentNode.vue';
import type { TableNode, TableRowNode } from '@/types/blog-content';

/**
 * Native RichEditor table. Wrapped in a focusable, named region so wide
 * tables scroll horizontally (also by keyboard) without breaking the page.
 * The region is named by the caption when there is one.
 */
const props = defineProps<{
    node: TableNode;
}>();

const captionId = useId();

/** A first row made only of header cells becomes the table's <thead>. */
const sections = computed<{ head: TableRowNode | null; body: TableRowNode[] }>(
    () => {
        const [firstRow, ...otherRows] = props.node.content;
        const isHeaderRow =
            firstRow !== undefined &&
            firstRow.content.every((cell) => cell.type === 'tableHeader');

        return isHeaderRow
            ? { head: firstRow, body: otherRows }
            : { head: null, body: props.node.content };
    },
);
</script>

<template>
    <div
        class="ni-table"
        role="region"
        tabindex="0"
        :aria-labelledby="node.caption ? captionId : undefined"
        :aria-label="node.caption ? undefined : 'Table'"
    >
        <table>
            <caption v-if="node.caption" :id="captionId">
                {{
                    node.caption
                }}
            </caption>
            <thead v-if="sections.head">
                <tr>
                    <th
                        v-for="(cell, cellIndex) in sections.head.content"
                        :key="cellIndex"
                        scope="col"
                        :colspan="cell.colspan > 1 ? cell.colspan : undefined"
                        :rowspan="cell.rowspan > 1 ? cell.rowspan : undefined"
                    >
                        <ContentNode
                            v-for="(child, childIndex) in cell.content"
                            :key="childIndex"
                            :node="child"
                        />
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, rowIndex) in sections.body" :key="rowIndex">
                    <component
                        :is="cell.type === 'tableHeader' ? 'th' : 'td'"
                        v-for="(cell, cellIndex) in row.content"
                        :key="cellIndex"
                        :scope="cell.type === 'tableHeader' ? 'row' : undefined"
                        :colspan="cell.colspan > 1 ? cell.colspan : undefined"
                        :rowspan="cell.rowspan > 1 ? cell.rowspan : undefined"
                    >
                        <ContentNode
                            v-for="(child, childIndex) in cell.content"
                            :key="childIndex"
                            :node="child"
                        />
                    </component>
                </tr>
            </tbody>
        </table>
    </div>
</template>
