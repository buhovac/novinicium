<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import type { TocEntry } from '@/types/blog-content';

/**
 * Table of contents: plain in-page links to the server-generated heading
 * IDs, in a native <details> disclosure so it works with keyboard, screen
 * readers and without JavaScript. Collapsed on small screens (and in the
 * server HTML); opened automatically on wide screens once the page runs.
 * Smooth scrolling is CSS-only and off for prefers-reduced-motion.
 */
defineProps<{
    entries: TocEntry[];
}>();

const WIDE_SCREEN = '(min-width: 64rem)';

const isOpen = ref(false);
let wideScreen: MediaQueryList | null = null;

function followScreenWidth(): void {
    isOpen.value = wideScreen?.matches ?? false;
}

function syncOpen(event: Event): void {
    isOpen.value = (event.target as HTMLDetailsElement).open;
}

onMounted(() => {
    wideScreen = window.matchMedia(WIDE_SCREEN);
    followScreenWidth();
    wideScreen.addEventListener('change', followScreenWidth);
});

onBeforeUnmount(() =>
    wideScreen?.removeEventListener('change', followScreenWidth),
);
</script>

<template>
    <details class="ni-toc" :open="isOpen" @toggle="syncOpen">
        <summary>On this page</summary>
        <nav aria-label="Table of contents">
            <ol>
                <li v-for="entry in entries" :key="entry.id">
                    <a :href="`#${entry.id}`">{{ entry.text }}</a>
                    <ol v-if="entry.children.length">
                        <li v-for="child in entry.children" :key="child.id">
                            <a :href="`#${child.id}`">{{ child.text }}</a>
                        </li>
                    </ol>
                </li>
            </ol>
        </nav>
    </details>
</template>
