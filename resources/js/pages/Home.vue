<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface Service {
    title: string;
    body: string;
}

interface SiteCopy {
    head_title: string;
    hero_kicker: string;
    hero_title: string;
    hero_subtitle: string;
    hero_primary_label: string;
    hero_secondary_label: string;
    services_title: string;
    services: Service[];
    cta_title: string;
    cta_subtitle: string;
    cta_button_label: string;
}

const page = usePage();

/**
 * Backend always merges stored values over defaults (SiteSetting::shared()),
 * so these client-side fallbacks only matter if the share is ever removed.
 */
const site = computed<SiteCopy>(() => ({
    head_title: 'Example Studio',
    hero_kicker: 'Web development studio',
    hero_title: 'Your headline goes here.',
    hero_subtitle: 'Edit this text in the admin panel under Site Settings.',
    hero_primary_label: 'View our work',
    hero_secondary_label: 'Get in touch',
    services_title: 'What we do',
    services: [],
    cta_title: 'Ready to start your project?',
    cta_subtitle: 'Tell us what you are trying to solve.',
    cta_button_label: 'Contact us',
    ...(page.props.site as Partial<SiteCopy> | undefined),
}));
</script>

<template>
    <Head :title="site.head_title" />

    <PublicLayout>
        <!-- Hero -->
        <section class="bg-[var(--ni-green)]" aria-labelledby="hero-title">
            <div class="mx-auto max-w-6xl px-6 py-20 sm:py-28">
                <p
                    class="text-sm font-semibold tracking-widest text-[var(--ni-gray)] uppercase"
                >
                    {{ site.hero_kicker }}
                </p>
                <h1
                    id="hero-title"
                    class="mt-4 max-w-2xl text-4xl leading-tight font-bold text-[var(--ni-gray)] sm:text-5xl"
                >
                    {{ site.hero_title }}
                </h1>
                <p
                    class="mt-6 max-w-xl text-lg leading-relaxed text-[var(--ni-gray)]"
                >
                    {{ site.hero_subtitle }}
                </p>
                <div class="mt-9 flex flex-wrap gap-3">
                    <Link
                        href="/portfolio"
                        class="rounded-lg bg-[var(--ni-gray)] px-5 py-3 font-medium text-white transition-transform hover:-translate-y-0.5"
                    >
                        {{ site.hero_primary_label }}
                    </Link>
                    <Link
                        href="/contact"
                        class="rounded-lg border-2 border-[var(--ni-gray)] px-5 py-3 font-medium text-[var(--ni-gray)] transition-colors hover:bg-[var(--ni-green-light)]"
                    >
                        {{ site.hero_secondary_label }}
                    </Link>
                </div>
            </div>
        </section>

        <!-- What we do -->
        <section
            class="mx-auto max-w-6xl px-6 py-20"
            aria-labelledby="services-title"
        >
            <h2 id="services-title" class="text-2xl font-semibold">
                {{ site.services_title }}
            </h2>
            <div class="mt-8 grid gap-6 md:grid-cols-3">
                <article
                    v-for="service in site.services"
                    :key="service.title"
                    class="rounded-xl border border-[var(--ni-gray-lightest)] p-6"
                >
                    <h3 class="text-xl font-medium">{{ service.title }}</h3>
                    <p class="mt-3 leading-relaxed text-[var(--ni-gray-light)]">
                        {{ service.body }}
                    </p>
                </article>
            </div>
        </section>

        <!-- CTA band -->
        <section
            class="bg-dark bg-[var(--ni-gray)]"
            aria-labelledby="cta-title"
        >
            <div class="mx-auto max-w-6xl px-6 py-16 text-white">
                <h2 id="cta-title" class="text-3xl font-semibold">
                    {{ site.cta_title }}
                </h2>
                <p class="mt-3 max-w-lg text-[var(--ni-gray-lightest)]">
                    {{ site.cta_subtitle }}
                </p>
                <Link
                    href="/contact"
                    class="on-dark mt-7 inline-block rounded-lg bg-[var(--ni-green)] px-5 py-3 font-medium text-[var(--ni-gray)] transition-transform hover:-translate-y-0.5"
                >
                    {{ site.cta_button_label }}
                </Link>
            </div>
        </section>
    </PublicLayout>
</template>
