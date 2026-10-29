<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, watch } from 'vue';
import ArticleToc from '@/components/blog/ArticleToc.vue';
import BlogContent from '@/components/blog/BlogContent.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { BlockNode, TocEntry } from '@/types/blog-content';

// content is a server-normalized node tree (App\Actions\Blog\BuildPostContent),
// rendered through components — no v-html anywhere on the blog.
const props = defineProps<{
    post: {
        title: string;
        content: BlockNode[];
        toc: TocEntry[];
        reading_minutes: number;
        image_path: string;
        author: string;
        /** Null only in an admin preview of a draft. */
        published_at: string | null;
        published_human: string | null;
        /** Set when the post changed on a later day than it was published. */
        updated_at: string | null;
        updated_human: string | null;
    };
    /** Set only on the admin preview route (BlogController::preview). */
    preview: {
        status: 'draft' | 'scheduled' | 'published';
        scheduled_human: string | null;
    } | null;
}>();

const headingCount = computed(() =>
    props.post.toc.reduce(
        (count, entry) => count + 1 + entry.children.length,
        0,
    ),
);

/**
 * The article's SEO/social tags are rendered once by the server (see
 * resources/views/app.blade.php). Remove them when this article is left
 * through an in-app visit, so they never describe a different page.
 */
function removeServerRenderedSeo(): void {
    document
        .querySelectorAll('[data-ni-article-seo]')
        .forEach((element) => element.remove());
}

watch(() => props.post, removeServerRenderedSeo);
onBeforeUnmount(removeServerRenderedSeo);
</script>

<template>
    <Head :title="`${post.title} — Nov Inicium Blog`" />

    <PublicLayout>
        <article class="mx-auto max-w-3xl px-6 py-16">
            <p
                v-if="preview"
                class="mb-6 rounded-lg border-2 border-dashed border-[var(--ni-gray)] bg-[var(--ni-gray-lightest)] p-4 text-[var(--ni-gray)]"
            >
                <strong>Preview.</strong>
                <template v-if="preview.status === 'draft'">
                    This article is a draft and is not public.
                </template>
                <template v-else-if="preview.status === 'scheduled'">
                    This article is scheduled for
                    {{ preview.scheduled_human }} and is not public yet.
                </template>
                <template v-else>
                    This article is published; the public page looks the same.
                </template>
                Only signed-in administrators can open this preview.
            </p>

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
                    <time
                        v-if="post.published_at"
                        :datetime="post.published_at"
                        >{{ post.published_human }}</time
                    >
                    <template v-else>Not published yet</template>
                    <template v-if="post.updated_at">
                        · Updated
                        <time :datetime="post.updated_at">{{
                            post.updated_human
                        }}</time>
                    </template>
                    · {{ post.reading_minutes }} min read
                </p>
            </header>

            <img
                :src="post.image_path"
                :alt="`Cover illustration for the article “${post.title}”`"
                class="mt-8 aspect-video w-full rounded-xl object-cover"
            />

            <ArticleToc
                v-if="headingCount >= 2"
                :entries="post.toc"
                class="mt-10"
            />

            <BlogContent :nodes="post.content" class="mt-10 text-lg" />
        </article>
    </PublicLayout>
</template>
