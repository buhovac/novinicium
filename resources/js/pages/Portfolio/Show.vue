<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface ProjectImage {
    id: number;
    image_path: string;
    alt_text: string;
}

// body is HTML from the admin RichEditor (owner-authored → v-html is safe).
const props = defineProps<{
    project: {
        title: string;
        description: string;
        body: string;
        live_url: string | null;
        images: ProjectImage[];
    };
}>();

/* ---- Accessible gallery ----
 * Thumbnails are real <button>s (keyboard operable by default).
 * The selected state is exposed via aria-pressed, the change is announced
 * through an aria-live region, and there is no focus trap — plain tab order.
 */
const activeIndex = ref(0);
const activeImage = computed(() => props.project.images[activeIndex.value]);

const liveAnnouncement = computed(() =>
    props.project.images.length
        ? `Image ${activeIndex.value + 1} of ${props.project.images.length}: ${activeImage.value.alt_text}`
        : '',
);

function select(index: number) {
    activeIndex.value = index;
}
</script>

<template>
    <Head :title="`${project.title} — Portfolio — Nov Inicium`" />

    <PublicLayout>
        <article class="mx-auto max-w-4xl px-6 py-16">
            <p>
                <Link href="/portfolio" class="text-sm font-medium underline underline-offset-4">
                    ← All projects
                </Link>
            </p>

            <header class="mt-6">
                <h1 class="text-4xl font-bold leading-tight">{{ project.title }}</h1>
                <p class="mt-4 max-w-2xl text-lg text-[var(--ni-gray-light)]">
                    {{ project.description }}
                </p>
                <p v-if="project.live_url" class="mt-5">
                    
                        :href="project.live_url"
                        rel="noopener"
                        class="inline-block rounded-lg bg-[var(--ni-green)] px-4 py-2.5 text-sm font-medium text-[var(--ni-gray)]"
                    >
                        Visit live project<span class="visually-hidden"> (opens external site)</span>
                    </a>
                </p>
            </header>

            <!-- Gallery -->
            <section v-if="project.images.length" class="mt-10" aria-labelledby="gallery-title">
                <h2 id="gallery-title" class="text-xl font-semibold">Screenshots</h2>

                <figure class="mt-4">
                    <img
                        :src="activeImage.image_path"
                        :alt="activeImage.alt_text"
                        class="aspect-video w-full rounded-xl border border-[var(--ni-gray-lightest)] object-cover"
                    />
                    <figcaption class="mt-2 text-sm text-[var(--ni-gray-light)]">
                        {{ activeImage.alt_text }}
                    </figcaption>
                </figure>

                <!-- Announces the change politely to screen readers -->
                <p class="visually-hidden" aria-live="polite">{{ liveAnnouncement }}</p>

                <ul v-if="project.images.length > 1" class="mt-4 flex flex-wrap gap-3" aria-label="Choose a screenshot">
                    <li v-for="(image, index) in project.images" :key="image.id">
                        <button
                            type="button"
                            :aria-pressed="index === activeIndex"
                            class="block overflow-hidden rounded-lg border-2 transition-colors"
                            :class="index === activeIndex ? 'border-[var(--ni-gray)]' : 'border-[var(--ni-gray-lightest)] hover:border-[var(--ni-gray-lighter)]'"
                            @click="select(index)"
                        >
                            <img
                                :src="image.image_path"
                                :alt="`Show screenshot ${index + 1}: ${image.alt_text}`"
                                class="h-20 w-32 object-cover"
                            />
                        </button>
                    </li>
                </ul>
            </section>

            <div class="ni-prose mt-10 text-lg" v-html="project.body" />
        </article>
    </PublicLayout>
</template>
