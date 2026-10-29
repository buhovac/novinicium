<script setup lang="ts">
import { computed, useId } from 'vue';
import type {
    QuizQuestionNode,
    QuizQuestionStatus,
} from '@/types/blog-content';

/**
 * One quiz question (state is owned by ArticleQuiz). A native radio group
 * in a fieldset whose legend is the question, so arrow keys, focus and
 * screen reader grouping come from the browser; a real button checks it.
 *
 * The result is text ("Correct." / "Not quite." plus the correct answer and
 * the explanation) in a polite live region that is always in the DOM, so it
 * is announced when filled without moving focus. After checking, the
 * options are also tagged in text ("correct answer", "your answer"), so
 * nothing depends on colour. Everything is interpolated, never v-html.
 */
const props = defineProps<{
    question: QuizQuestionNode;
    number: number;
    total: number;
    selected: number | null;
    status: QuizQuestionStatus;
}>();

const emit = defineEmits<{
    select: [optionIndex: number];
    check: [];
}>();

const groupName = `ni-quiz-${useId()}`;

const isChecked = computed(() => props.status === 'checked');
const isCorrect = computed(
    () => isChecked.value && props.selected === props.question.correctIndex,
);
const correctAnswer = computed(
    () => props.question.options[props.question.correctIndex],
);

function optionTag(index: number): string | null {
    if (!isChecked.value) {
        return null;
    }

    if (index === props.question.correctIndex) {
        return index === props.selected
            ? 'your answer, correct'
            : 'correct answer';
    }

    return index === props.selected ? 'your answer' : null;
}
</script>

<template>
    <fieldset class="ni-quiz-question">
        <legend>
            <span class="ni-quiz-number"
                >Question {{ number }} of {{ total }}</span
            >
            {{ question.question }}
        </legend>

        <div class="ni-quiz-options">
            <div
                v-for="(option, index) in question.options"
                :key="index"
                class="ni-quiz-option"
                :data-result="
                    isChecked && index === question.correctIndex
                        ? 'correct'
                        : isChecked && index === selected
                          ? 'incorrect'
                          : undefined
                "
            >
                <input
                    :id="`${groupName}-${index}`"
                    type="radio"
                    :name="groupName"
                    :value="index"
                    :checked="selected === index"
                    @change="emit('select', index)"
                />
                <label :for="`${groupName}-${index}`">
                    {{ option }}
                    <span v-if="optionTag(index)" class="ni-quiz-tag"
                        >({{ optionTag(index) }})</span
                    >
                </label>
            </div>
        </div>

        <button type="button" class="ni-quiz-check" @click="emit('check')">
            Check answer<span class="sr-only"> to question {{ number }}</span>
        </button>

        <div class="ni-quiz-result" aria-live="polite" aria-atomic="true">
            <p v-if="status === 'needsAnswer'" class="ni-quiz-verdict">
                Choose an answer first.
            </p>
            <template v-else-if="isChecked">
                <p
                    class="ni-quiz-verdict"
                    :data-result="isCorrect ? 'correct' : 'incorrect'"
                >
                    <strong>{{ isCorrect ? 'Correct.' : 'Not quite.' }}</strong>
                    <template v-if="!isCorrect">
                        The correct answer is: {{ correctAnswer }}.</template
                    >
                </p>
                <p class="ni-quiz-explanation">{{ question.explanation }}</p>
            </template>
        </div>
    </fieldset>
</template>
