<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import BlogContent from '@/components/blog/BlogContent.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { BlockNode } from '@/types/blog-content';

// content is a server-normalized node tree (App\Actions\Blog\BuildPostContent),
// rendered through components — no v-html anywhere on the blog.
defineProps<{
    post: {
        title: string;
        content: BlockNode[];
        image_path: string;
        author: string;
        published_at: string;
        published_human: string;
    };
}>();
</script>

<template>
    <Head :title="`${post.title} — Nov Inicium Blog`" />

    <PublicLayout>
        <article class="mx-auto max-w-3xl px-6 py-16">
            <p>
                <Link
                    href="/blog"
                    class="text-sm font-medium underline underline-offset-4"
                >
                    ← All articles
                </Link>
            </p>

            <header class="mt-6">
                <h1 class="text-4xl leading-tight font-bold">
                    {{ post.title }}
                </h1>
                <p class="mt-4 text-[var(--ni-gray-light)]">
                    By {{ post.author }} ·
                    <time :datetime="post.published_at">{{
                        post.published_human
                    }}</time>
                </p>
            </header>

            <img
                :src="post.image_path"
                :alt="`Cover illustration for the article “${post.title}”`"
                class="mt-8 aspect-video w-full rounded-xl object-cover"
            />

            <BlogContent :nodes="post.content" class="mt-10 text-lg" />
        </article>
    </PublicLayout>
</template>
