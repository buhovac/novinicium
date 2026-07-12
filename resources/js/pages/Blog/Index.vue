<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface PostCard {
    title: string;
    slug: string;
    excerpt: string;
    image_path: string;
    author: string;
    published_at: string;
    published_human: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

defineProps<{
    posts: {
        data: PostCard[];
        links: PaginationLink[];
    };
}>();
</script>

<template>
    <Head title="Blog — Nov Inicium" />

    <PublicLayout>
        <div class="mx-auto max-w-6xl px-6 py-16">
            <h1 class="text-4xl font-bold">Blog</h1>
            <p class="mt-3 max-w-xl text-lg text-[var(--ni-gray-light)]">
                Notes from real projects: web development, accessibility and building
                products for small businesses.
            </p>

            <p v-if="posts.data.length === 0" class="mt-12 text-[var(--ni-gray-light)]">
                No articles published yet — check back soon.
            </p>

            <ul v-else class="mt-12 grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                <li v-for="post in posts.data" :key="post.slug">
                    <article class="flex h-full flex-col overflow-hidden rounded-xl border border-[var(--ni-gray-lightest)]">
                        <div class="ni-media aspect-video w-full">
                            <span class="ni-media-kicker">Blog</span>
                            <span class="ni-media-tag">Nov Inicium</span>
                            <img
                                :src="post.image_path"
                                :alt="`Cover illustration for the article “${post.title}”`"
                                loading="lazy"
                            />
                        </div>
                        <div class="flex flex-1 flex-col p-6">
                            <h2 class="text-xl font-semibold leading-snug">
                                <Link :href="`/blog/${post.slug}`" class="hover:underline">
                                    {{ post.title }}
                                </Link>
                            </h2>
                            <p class="mt-3 flex-1 leading-relaxed text-[var(--ni-gray-light)]">
                                {{ post.excerpt }}
                            </p>
                            <p class="mt-5 text-sm text-[var(--ni-gray-light)]">
                                {{ post.author }} ·
                                <time :datetime="post.published_at">{{ post.published_human }}</time>
                            </p>
                        </div>
                    </article>
                </li>
            </ul>

            <!-- Pagination -->
            <nav v-if="posts.links.length > 3" aria-label="Blog pagination" class="mt-12">
                <ul class="flex flex-wrap gap-1">
                    <li v-for="link in posts.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :aria-current="link.active ? 'page' : undefined"
                            class="inline-block rounded-lg px-3.5 py-2 text-sm"
                            :class="link.active ? 'bg-[var(--ni-gray)] text-white' : 'text-[var(--ni-gray)] hover:bg-[var(--ni-gray-lightest)]'"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="inline-block px-3.5 py-2 text-sm text-[var(--ni-gray-lighter)]"
                            aria-hidden="true"
                            v-html="link.label"
                        />
                    </li>
                </ul>
            </nav>
        </div>
    </PublicLayout>
</template>
