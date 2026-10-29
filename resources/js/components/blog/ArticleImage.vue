<script setup lang="ts">
import type { ImageBlockNode } from '@/types/blog-content';

/**
 * Image block. Alt and caption are separate: alt replaces the image for
 * screen readers, the caption is extra visible text for everyone.
 * Decorative images get alt="" and no caption, so assistive tech skips them.
 */
defineProps<{
    node: ImageBlockNode;
}>();
</script>

<template>
    <div v-if="node.decorative" class="ni-figure" :data-width="node.width">
        <img :src="node.src" alt="" loading="lazy" decoding="async" />
    </div>
    <figure v-else class="ni-figure" :data-width="node.width">
        <img :src="node.src" :alt="node.alt" loading="lazy" decoding="async" />
        <figcaption v-if="node.caption || node.credit">
            <span v-if="node.caption">{{ node.caption }}</span>
            <span v-if="node.credit" class="ni-figure-credit">{{
                node.credit
            }}</span>
        </figcaption>
    </figure>
</template>
