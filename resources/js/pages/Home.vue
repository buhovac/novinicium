<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';

const services = [
    {
        title: 'Mini SaaS Products',
        body: 'Custom-built SaaS solutions tailored to your business needs — from booking platforms to internal tools.',
    },
    {
        title: 'Custom Websites',
        body: 'WordPress and Drupal sites built to your exact specifications, from design to deployment.',
    },
    {
        title: 'Video Tutorials',
        body: 'Educational content for developers and businesses: real projects, honest walkthroughs.',
    },
];
</script>

<template>
    <Head title="Nov Inicium — SaaS products & custom web development" />

    <PublicLayout>
        <!-- Hero -->
        <section class="bg-[var(--ni-green)]" aria-labelledby="hero-title">
            <div class="mx-auto max-w-6xl px-6 py-20 sm:py-28">
                <p class="text-sm font-semibold uppercase tracking-widest text-[var(--ni-gray)]">
                    Web development studio — Belgium
                </p>
                <h1 id="hero-title" class="mt-4 max-w-2xl text-4xl font-bold leading-tight text-[var(--ni-gray)] sm:text-5xl">
                    Software that solves one problem well.
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-relaxed text-[var(--ni-gray)]">
                    We design and build focused SaaS products and custom websites for small
                    businesses — and we document how, so you can learn from every build.
                </p>
                <div class="mt-9 flex flex-wrap gap-3">
                    <Link
                        href="/portfolio"
                        class="rounded-lg bg-[var(--ni-gray)] px-5 py-3 font-medium text-white transition-transform hover:-translate-y-0.5"
                    >
                        View our work
                    </Link>
                    <Link
                        href="/contact"
                        class="rounded-lg border-2 border-[var(--ni-gray)] px-5 py-3 font-medium text-[var(--ni-gray)] transition-colors hover:bg-[var(--ni-green-light)]"
                    >
                        Get in touch
                    </Link>
                </div>
            </div>
        </section>

        <!-- What we do -->
        <section class="mx-auto max-w-6xl px-6 py-20" aria-labelledby="services-title">
            <h2 id="services-title" class="text-2xl font-semibold">What we do</h2>
            <div class="mt-8 grid gap-6 md:grid-cols-3">
                <article
                    v-for="service in services"
                    :key="service.title"
                    class="rounded-xl border border-[var(--ni-gray-lightest)] p-6"
                >
                    <h3 class="text-xl font-medium">{{ service.title }}</h3>
                    <p class="mt-3 leading-relaxed text-[var(--ni-gray-light)]">{{ service.body }}</p>
                </article>
            </div>
        </section>

        <!-- CTA band -->
        <section class="bg-dark bg-[var(--ni-gray)]" aria-labelledby="cta-title">
            <div class="mx-auto max-w-6xl px-6 py-16 text-white">
                <h2 id="cta-title" class="text-3xl font-semibold">Ready to start your project?</h2>
                <p class="mt-3 max-w-lg text-[var(--ni-gray-lightest)]">
                    Tell us what you are trying to solve. We reply within one business day.
                </p>
                <Link
                    href="/contact"
                    class="on-dark mt-7 inline-block rounded-lg bg-[var(--ni-green)] px-5 py-3 font-medium text-[var(--ni-gray)] transition-transform hover:-translate-y-0.5"
                >
                    Contact us
                </Link>
            </div>
        </section>
    </PublicLayout>
</template>
