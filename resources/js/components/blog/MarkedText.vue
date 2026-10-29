<script setup lang="ts">
import { computed } from 'vue';
import type { ContentMark } from '@/types/blog-content';

/**
 * Renders text wrapped in its marks, outermost first, by recursing on the
 * remaining marks. Text is always interpolated, never parsed as HTML.
 */
const props = defineProps<{
    text: string;
    marks: ContentMark[];
}>();

const mark = computed(() => props.marks[0]);
const remainingMarks = computed(() => props.marks.slice(1));
</script>

<template>
    <template v-if="!mark">{{ text }}</template>
    <strong v-else-if="mark.type === 'bold'"
        ><MarkedText :text="text" :marks="remainingMarks"
    /></strong>
    <em v-else-if="mark.type === 'italic'"
        ><MarkedText :text="text" :marks="remainingMarks"
    /></em>
    <code v-else-if="mark.type === 'code'"
        ><MarkedText :text="text" :marks="remainingMarks"
    /></code>
    <a
        v-else-if="mark.type === 'link' && mark.newTab"
        :href="mark.href"
        target="_blank"
        rel="noopener noreferrer"
        ><MarkedText :text="text" :marks="remainingMarks" /><span
            class="sr-only"
        >
            (opens in a new tab)</span
        ></a
    >
    <a v-else-if="mark.type === 'link'" :href="mark.href"
        ><MarkedText :text="text" :marks="remainingMarks"
    /></a>
</template>
