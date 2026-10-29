<script setup lang="ts">
import {
    BookOpen,
    CircleAlert,
    Info,
    Lightbulb,
    TriangleAlert,
} from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import { computed, useId } from 'vue';
import type { CalloutNode, CalloutVariant } from '@/types/blog-content';

/**
 * Callout block. The variant is carried by a visible text label (with a
 * decorative icon), never by colour alone. The <aside> is named by its
 * header, so screen readers announce e.g. "Warning: Check contrast".
 */
const props = defineProps<{
    node: CalloutNode;
}>();

const icons: Record<CalloutVariant, LucideIcon> = {
    information: Info,
    definition: BookOpen,
    important: CircleAlert,
    warning: TriangleAlert,
    example: Lightbulb,
};

const headerId = useId();
const icon = computed(() => icons[props.node.variant] ?? Info);
</script>

<template>
    <aside
        class="ni-callout"
        :data-variant="node.variant"
        :aria-labelledby="headerId"
    >
        <p :id="headerId" class="ni-callout-header">
            <component
                :is="icon"
                class="ni-callout-icon"
                aria-hidden="true"
                focusable="false"
            />
            <span class="ni-callout-label">{{ node.label }}</span
            ><span class="sr-only">: </span>
            <span class="ni-callout-title">{{ node.title }}</span>
        </p>
        <p
            v-for="(paragraph, index) in node.paragraphs"
            :key="index"
            class="ni-callout-text"
        >
            {{ paragraph }}
        </p>
    </aside>
</template>
