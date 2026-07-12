<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface ProjectCard {
    title: string;
    slug: string;
    description: string;
    cover: { image_path: string; alt_text: string } | null;
}

defineProps<{ projects: ProjectCard[] }>();
</script>

<template>
    <Head title="Portfolio — Nov Inicium" />

    <PublicLayout>
        <div class="mx-auto max-w-6xl px-6 py-16">
            <h1 class="text-4xl font-bold">Portfolio</h1>
            <p class="mt-3 max-w-xl text-lg text-[var(--ni-gray-light)]">
                Selected projects: SaaS products, AI applications and custom CMS work.
            </p>

            <ul class="mt-12 grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                <li v-for="project in projects" :key="project.slug">
                    <article class="flex h-full flex-col overflow-hidden rounded-xl border border-[var(--ni-gray-lightest)]">
                        <div v-if="project.cover" class="ni-media aspect-video w-full">
                            <span class="ni-media-kicker">Project</span>
                            <span class="ni-media-tag">Nov Inicium</span>
                            <img
                                :src="project.cover.image_path"
                                :alt="project.cover.alt_text"
                                loading="lazy"
                            />
                        </div>
                        <div class="flex flex-1 flex-col p-6">
                            <h2 class="text-xl font-semibold leading-snug">
                                <Link :href="`/portfolio/${project.slug}`" class="hover:underline">
                                    {{ project.title }}
                                </Link>
                            </h2>
                            <p class="mt-3 flex-1 leading-relaxed text-[var(--ni-gray-light)]">
                                {{ project.description }}
                            </p>
                            <p class="mt-5">
                                <Link
                                    :href="`/portfolio/${project.slug}`"
                                    class="text-sm font-medium underline underline-offset-4"
                                >
                                    View project<span class="visually-hidden">: {{ project.title }}</span>
                                </Link>
                            </p>
                        </div>
                    </article>
                </li>
            </ul>
        </div>
    </PublicLayout>
</template>
