<script setup lang="ts">
import MarkedText from '@/components/blog/MarkedText.vue';
import type { InlineNode } from '@/types/blog-content';

defineProps<{
    nodes: InlineNode[];
}>();
</script>

<template>
    <template v-for="(node, index) in nodes" :key="index">
        <MarkedText
            v-if="node.type === 'text'"
            :text="node.text"
            :marks="node.marks"
        /><br v-else-if="node.type === 'hardBreak'" /><img
            v-else-if="node.type === 'image'"
            :src="node.src"
            :alt="node.alt"
            loading="lazy"
        />
    </template>
</template>
