<script setup lang="ts">
import { computed, ref, useId } from 'vue';
import type { ComparisonImage } from '@/types/blog-content';

/**
 * Optional slider view for ArticleImageComparison. The control is a native
 * <input type="range">, so keyboard (arrows, Page Up/Down, Home/End), focus,
 * touch and screen reader support come from the browser. There is no
 * separate drag handle: the images are not interactive.
 *
 * 0% shows only the first image, 100% only the second. Both images stay in
 * the accessibility tree with their alt text at every position.
 */
const props = defineProps<{
    first: ComparisonImage;
    second: ComparisonImage;
}>();

const inputId = `ni-compare-${useId()}`;
const position = ref(50);

const firstName = computed(() => props.first.label ?? 'first image');
const secondName = computed(() => props.second.label ?? 'second image');

const valueText = computed(
    () =>
        `${position.value}% ${secondName.value}, ${100 - position.value}% ${firstName.value}`,
);
</script>

<template>
    <div class="ni-compare-slider">
        <div
            class="ni-compare-stage"
            :style="{ '--ni-compare-position': `${position}%` }"
        >
            <img
                class="ni-compare-base"
                :src="first.src"
                :alt="first.alt"
                decoding="async"
            />
            <img
                class="ni-compare-overlay"
                :src="second.src"
                :alt="second.alt"
                decoding="async"
            />
            <span class="ni-compare-divider" aria-hidden="true"></span>
            <!-- Visual tags only; the same names are in the input label. -->
            <span
                v-if="second.label"
                class="ni-compare-tag"
                data-side="second"
                aria-hidden="true"
                >{{ second.label }}</span
            >
            <span
                v-if="first.label"
                class="ni-compare-tag"
                data-side="first"
                aria-hidden="true"
                >{{ first.label }}</span
            >
        </div>
        <label :for="inputId" class="ni-compare-label">
            Reveal comparison: 0% shows {{ firstName }}, 100% shows
            {{ secondName }}
        </label>
        <input
            :id="inputId"
            v-model.number="position"
            class="ni-compare-range"
            type="range"
            min="0"
            max="100"
            step="1"
            :aria-valuetext="valueText"
        />
    </div>
</template>
