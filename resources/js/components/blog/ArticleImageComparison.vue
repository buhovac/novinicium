<script setup lang="ts">
import { onMounted, ref } from 'vue';
import ImageComparisonSlider from '@/components/blog/ImageComparisonSlider.vue';
import type { ImageComparisonNode } from '@/types/blog-content';

/**
 * Image comparison. The canonical presentation is both images side by side
 * (stacked on small screens), each with its alt text and optional label.
 * It is what the server renders and what works without JavaScript.
 *
 * Once the page is interactive, a view switch offers an optional slider
 * (a native range input). Side by side stays the default and is always one
 * click away, so the slider is never the only way to see the content.
 */
defineProps<{
    node: ImageComparisonNode;
}>();

const canUseSlider = ref(false);
const view = ref<'sideBySide' | 'slider'>('sideBySide');

onMounted(() => {
    canUseSlider.value = true;
});
</script>

<template>
    <figure class="ni-compare">
        <div
            v-if="canUseSlider"
            class="ni-compare-views"
            role="group"
            aria-label="Comparison view"
        >
            <button
                type="button"
                :aria-pressed="view === 'sideBySide'"
                @click="view = 'sideBySide'"
            >
                Side by side
            </button>
            <button
                type="button"
                :aria-pressed="view === 'slider'"
                @click="view = 'slider'"
            >
                Slider
            </button>
        </div>

        <ImageComparisonSlider
            v-if="canUseSlider && view === 'slider'"
            :first="node.first"
            :second="node.second"
        />
        <div v-else class="ni-compare-grid">
            <figure
                v-for="(image, index) in [node.first, node.second]"
                :key="index"
                class="ni-compare-item"
            >
                <img
                    :src="image.src"
                    :alt="image.alt"
                    loading="lazy"
                    decoding="async"
                />
                <figcaption v-if="image.label">{{ image.label }}</figcaption>
            </figure>
        </div>

        <figcaption v-if="node.caption" class="ni-compare-caption">
            {{ node.caption }}
        </figcaption>
    </figure>
</template>
