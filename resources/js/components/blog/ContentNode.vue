<script setup lang="ts">
import { computed } from 'vue';
import ArticleCallout from '@/components/blog/ArticleCallout.vue';
import InlineContent from '@/components/blog/InlineContent.vue';
import type { BlockNode, TableRowNode } from '@/types/blog-content';

/** Maps one server-normalized block node to semantic HTML (recursive). */
const props = defineProps<{
    node: BlockNode;
}>();

/** A first row made only of header cells becomes the table's <thead>. */
const tableSections = computed<{
    head: TableRowNode | null;
    body: TableRowNode[];
}>(() => {
    if (props.node.type !== 'table') {
        return { head: null, body: [] };
    }

    const [firstRow, ...otherRows] = props.node.content;
    const isHeaderRow = firstRow.content.every(
        (cell) => cell.type === 'tableHeader',
    );

    return isHeaderRow
        ? { head: firstRow, body: otherRows }
        : { head: null, body: props.node.content };
});
</script>

<template>
    <p v-if="node.type === 'paragraph'">
        <InlineContent :nodes="node.content" />
    </p>

    <component :is="`h${node.level}`" v-else-if="node.type === 'heading'">
        <InlineContent :nodes="node.content" />
    </component>

    <ul v-else-if="node.type === 'bulletList'">
        <li v-for="(item, index) in node.content" :key="index">
            <ContentNode
                v-for="(child, childIndex) in item.content"
                :key="childIndex"
                :node="child"
            />
        </li>
    </ul>

    <ol
        v-else-if="node.type === 'orderedList'"
        :start="node.start !== 1 ? node.start : undefined"
    >
        <li v-for="(item, index) in node.content" :key="index">
            <ContentNode
                v-for="(child, childIndex) in item.content"
                :key="childIndex"
                :node="child"
            />
        </li>
    </ol>

    <blockquote v-else-if="node.type === 'blockquote'">
        <ContentNode
            v-for="(child, index) in node.content"
            :key="index"
            :node="child"
        />
    </blockquote>

    <hr v-else-if="node.type === 'horizontalRule'" />

    <!-- Focusable so keyboard users can scroll long lines. -->
    <pre
        v-else-if="node.type === 'codeBlock'"
        tabindex="0"
    ><code :data-language="node.language ?? undefined">{{ node.text }}</code></pre>

    <!-- Scrollable, keyboard-focusable region so wide tables never break the layout. -->
    <div
        v-else-if="node.type === 'table'"
        class="ni-table"
        role="region"
        aria-label="Table"
        tabindex="0"
    >
        <table>
            <thead v-if="tableSections.head">
                <tr>
                    <th
                        v-for="(cell, cellIndex) in tableSections.head.content"
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
                <tr
                    v-for="(row, rowIndex) in tableSections.body"
                    :key="rowIndex"
                >
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

    <div
        v-else-if="node.type === 'grid'"
        class="ni-grid"
        :data-from="node.fromBreakpoint"
        :style="{ '--ni-grid-cols': node.columns }"
    >
        <div
            v-for="(column, index) in node.content"
            :key="index"
            :style="{ '--ni-grid-span': column.span }"
        >
            <ContentNode
                v-for="(child, childIndex) in column.content"
                :key="childIndex"
                :node="child"
            />
        </div>
    </div>

    <ArticleCallout v-else-if="node.type === 'callout'" :node="node" />
</template>
