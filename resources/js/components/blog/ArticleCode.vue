<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue';
import type { CodeNode } from '@/types/blog-content';

/**
 * Code block: plain text only (interpolated, never parsed or executed),
 * with a language label and a keyboard-accessible copy button whose result
 * is shown on the button and announced through a polite live region.
 */
const props = defineProps<{
    node: CodeNode;
}>();

const status = ref<'idle' | 'copied' | 'failed'>('idle');
let resetTimer: ReturnType<typeof setTimeout> | undefined;

async function copyCode(): Promise<void> {
    try {
        await navigator.clipboard.writeText(props.node.code);
        status.value = 'copied';
    } catch {
        status.value = 'failed';
    }

    clearTimeout(resetTimer);
    resetTimer = setTimeout(() => (status.value = 'idle'), 2500);
}

onBeforeUnmount(() => clearTimeout(resetTimer));
</script>

<template>
    <div class="ni-code">
        <div class="ni-code-bar">
            <span class="ni-code-language">{{ node.label }}</span>
            <button type="button" class="ni-code-copy" @click="copyCode">
                <template v-if="status === 'copied'">Copied</template>
                <template v-else-if="status === 'failed'">Copy failed</template>
                <template v-else
                    >Copy<span class="sr-only">
                        {{ node.label }} code</span
                    ></template
                >
            </button>
            <span class="sr-only" aria-live="polite">{{
                status === 'copied'
                    ? 'Code copied to clipboard'
                    : status === 'failed'
                      ? 'Could not copy the code. Select it and copy manually.'
                      : ''
            }}</span>
        </div>
        <!-- Focusable, named region so keyboard users can scroll long lines. -->
        <pre
            role="region"
            :aria-label="`${node.label} code example`"
            tabindex="0"
        ><code>{{ node.code }}</code></pre>
    </div>
</template>
