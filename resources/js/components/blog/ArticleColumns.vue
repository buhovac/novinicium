<script setup lang="ts">
import ContentNode from '@/components/blog/ContentNode.vue';
import type { GridNode } from '@/types/blog-content';

/**
 * Native RichEditor grid ("columns"). Stacked on narrow screens and side by
 * side from the chosen breakpoint. Columns are never reordered with CSS, so
 * screen reader and keyboard order always equal the visual order.
 */
defineProps<{
    node: GridNode;
}>();
</script>

<template>
    <div
        class="ni-grid"
        :data-from="node.fromBreakpoint"
        :style="{ '--ni-grid-cols': node.columns }"
    >
        <div
            v-for="(column, index) in node.content"
            :key="index"
            class="ni-grid-column"
            :style="{ '--ni-grid-span': column.span }"
        >
            <ContentNode
                v-for="(child, childIndex) in column.content"
                :key="childIndex"
                :node="child"
            />
        </div>
    </div>
</template>
