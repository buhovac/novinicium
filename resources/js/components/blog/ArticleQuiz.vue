<script setup lang="ts">
import { ref, useId, watch } from 'vue';
import QuizQuestion from '@/components/blog/QuizQuestion.vue';
import type { QuizNode, QuizQuestionStatus } from '@/types/blog-content';

/**
 * Quiz block: a self-check, one question at a time checked by the reader.
 * Answers live only in this component's state: nothing is stored, sent or
 * tracked, and a reload starts fresh. The correct answers are in the page
 * data by design (a learning aid, not an exam).
 */
const props = defineProps<{
    node: QuizNode;
}>();

const titleId = useId();
const answers = ref<(number | null)[]>([]);
const statuses = ref<QuizQuestionStatus[]>([]);

function reset(): void {
    answers.value = props.node.questions.map(() => null);
    statuses.value = props.node.questions.map(() => 'open');
}

reset();
watch(() => props.node, reset, { deep: true });

/** A new choice clears the previous result so it never looks stale. */
function select(questionIndex: number, optionIndex: number): void {
    answers.value[questionIndex] = optionIndex;
    statuses.value[questionIndex] = 'open';
}

function check(questionIndex: number): void {
    statuses.value[questionIndex] =
        answers.value[questionIndex] === null ? 'needsAnswer' : 'checked';
}
</script>

<template>
    <section
        class="ni-quiz"
        :aria-labelledby="node.title ? titleId : undefined"
        :aria-label="node.title ? undefined : 'Quiz'"
    >
        <p v-if="node.title" :id="titleId" class="ni-quiz-title">
            {{ node.title }}
        </p>
        <QuizQuestion
            v-for="(question, index) in node.questions"
            :key="index"
            :question="question"
            :number="index + 1"
            :total="node.questions.length"
            :selected="answers[index] ?? null"
            :status="statuses[index] ?? 'open'"
            @select="select(index, $event)"
            @check="check(index)"
        />
    </section>
</template>
