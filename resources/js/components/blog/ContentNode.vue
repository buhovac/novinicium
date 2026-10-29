<script setup lang="ts">
import ArticleCallout from '@/components/blog/ArticleCallout.vue';
import ArticleChart from '@/components/blog/ArticleChart.vue';
import ArticleCode from '@/components/blog/ArticleCode.vue';
import ArticleColumns from '@/components/blog/ArticleColumns.vue';
import ArticleImage from '@/components/blog/ArticleImage.vue';
import ArticleImageComparison from '@/components/blog/ArticleImageComparison.vue';
import ArticleQuiz from '@/components/blog/ArticleQuiz.vue';
import ArticleStatistics from '@/components/blog/ArticleStatistics.vue';
import ArticleTable from '@/components/blog/ArticleTable.vue';
import InlineContent from '@/components/blog/InlineContent.vue';
import type { BlockNode } from '@/types/blog-content';

/** Maps one server-normalized block node to semantic HTML (recursive). */
defineProps<{
    node: BlockNode;
}>();
</script>

<template>
    <p v-if="node.type === 'paragraph'">
        <InlineContent :nodes="node.content" />
    </p>

    <component
        :is="`h${node.level}`"
        v-else-if="node.type === 'heading'"
        :id="node.id"
    >
        <InlineContent :nodes="node.content" />
    </component>

    <ul v-else-if="node.type === 'bulletList'">
        <li v-for="(item, index) in node.content" :key="index">
            <ContentNode
                v-for="(child, childIndex) in item.content"
                :key="childIndex"
                :node="child"
            />
        </li>
    </ul>

    <ol
        v-else-if="node.type === 'orderedList'"
        :start="node.start !== 1 ? node.start : undefined"
    >
        <li v-for="(item, index) in node.content" :key="index">
            <ContentNode
                v-for="(child, childIndex) in item.content"
                :key="childIndex"
                :node="child"
            />
        </li>
    </ol>

    <blockquote v-else-if="node.type === 'blockquote'">
        <ContentNode
            v-for="(child, index) in node.content"
            :key="index"
            :node="child"
        />
    </blockquote>

    <hr v-else-if="node.type === 'horizontalRule'" />

    <ArticleCode v-else-if="node.type === 'code'" :node="node" />

    <ArticleTable v-else-if="node.type === 'table'" :node="node" />
    <ArticleColumns v-else-if="node.type === 'grid'" :node="node" />
    <ArticleCallout v-else-if="node.type === 'callout'" :node="node" />
    <ArticleImage v-else-if="node.type === 'image'" :node="node" />
    <ArticleStatistics v-else-if="node.type === 'statistics'" :node="node" />
    <ArticleImageComparison
        v-else-if="node.type === 'imageComparison'"
        :node="node"
    />
    <ArticleChart v-else-if="node.type === 'chart'" :node="node" />
    <ArticleQuiz v-else-if="node.type === 'quiz'" :node="node" />
</template>
