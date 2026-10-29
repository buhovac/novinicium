<script setup lang="ts">
import ContentNode from '@/components/blog/ContentNode.vue';
import type { BlockNode } from '@/types/blog-content';

/** The single public renderer for blog bodies (server-normalized nodes). */
defineProps<{
    nodes: BlockNode[];
}>();
</script>

<template>
    <div class="ni-prose">
        <ContentNode v-for="(node, index) in nodes" :key="index" :node="node" />
    </div>
</template>
