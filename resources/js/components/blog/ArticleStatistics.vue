<script setup lang="ts">
import type { StatisticsNode } from '@/types/blog-content';

/**
 * Statistics block. A plain list: screen readers announce the number of
 * figures, and each item reads as a sentence in visual order ("2.2 billion
 * people live with a vision impairment"), followed by its explanation and
 * source. Static: no count-up animation.
 * role="list" is explicit because Safari drops list semantics when
 * list-style is none.
 */
defineProps<{
    node: StatisticsNode;
}>();
</script>

<template>
    <ul class="ni-stats" role="list" :data-layout="node.layout">
        <li v-for="(item, index) in node.items" :key="index" class="ni-stat">
            <p class="ni-stat-figure">
                <span class="ni-stat-value"
                    >{{ item.value
                    }}<span v-if="item.unit" class="ni-stat-unit">{{
                        item.unit
                    }}</span></span
                >
                {{ ' ' }}
                <span class="ni-stat-label">{{ item.label }}</span>
            </p>
            <p v-if="item.explanation" class="ni-stat-explanation">
                {{ item.explanation }}
            </p>
            <p v-if="item.source" class="ni-stat-source">
                Source: {{ item.source }}
            </p>
        </li>
    </ul>
</template>
