<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';

const lastUpdated = 'July 2026'; // EDIT-ME: update when the statement changes
</script>

<template>
    <Head title="Accessibility — Nov Inicium" />

    <PublicLayout>
        <div class="mx-auto max-w-2xl px-6 py-16">
            <h1 class="text-4xl font-bold">Accessibility statement</h1>
            <p class="mt-3 text-lg text-[var(--ni-gray-light)]">
                We want everyone to be able to use this website, regardless of
                ability or the technology they browse with.
            </p>

            <div class="ni-prose mt-10 text-[var(--ni-gray)]">
                <section aria-labelledby="commitment">
                    <h2
                        id="commitment"
                        class="mt-10 mb-4 text-2xl font-semibold"
                    >
                        Our commitment
                    </h2>
                    <p>
                        Accessibility was a primary design constraint for this
                        site, not an afterthought. We aim to conform to the
                        <a
                            href="https://www.w3.org/TR/WCAG21/"
                            class="underline underline-offset-4"
                            rel="noopener"
                            >Web Content Accessibility Guidelines (WCAG) 2.1</a
                        >, level AA, and we exceed it where practical.
                    </p>
                </section>

                <section aria-labelledby="measures">
                    <h2 id="measures" class="mt-10 mb-4 text-2xl font-semibold">
                        What we've done
                    </h2>
                    <p>
                        The site is built with semantic HTML and named
                        landmarks, and every feature works with a keyboard
                        alone: a skip link is the first focusable element on
                        each page, focus is always clearly visible, and the
                        mobile menu communicates its state to assistive
                        technology.
                    </p>
                    <p>
                        All text colour pairings meet or exceed WCAG contrast
                        requirements — body text on white and text on our brand
                        green both reach level AAA (7:1 or better). Colour is
                        never the only way information is conveyed.
                    </p>
                    <p>
                        Images carry meaningful alternative text, forms label
                        every field and announce errors in a summary that
                        receives focus, and animations are disabled
                        automatically for visitors who prefer reduced motion.
                    </p>
                </section>

                <section aria-labelledby="status">
                    <h2 id="status" class="mt-10 mb-4 text-2xl font-semibold">
                        Conformance status
                    </h2>
                    <p>
                        We believe this website conforms to WCAG 2.1 level AA.
                        Content is reviewed against these criteria whenever
                        pages or components change. A website is never "done"
                        being accessible, so we treat this as an ongoing
                        responsibility rather than a checkbox.
                    </p>
                </section>

                <section aria-labelledby="feedback">
                    <h2 id="feedback" class="mt-10 mb-4 text-2xl font-semibold">
                        Found a problem?
                    </h2>
                    <p>
                        If any part of this site is difficult or impossible for
                        you to use, please tell us through the
                        <Link
                            href="/contact"
                            class="underline underline-offset-4"
                            >contact form</Link
                        >
                        or by email at
                        <a
                            href="mailto:contact@novinicium.be"
                            class="underline underline-offset-4"
                            >contact@novinicium.be</a
                        >.
                        <!-- EDIT-ME: potvrdi email -->
                        Describe the page and what went wrong, and we'll fix it
                        — accessibility reports are treated as bugs with
                        priority.
                    </p>
                </section>

                <p class="mt-12 text-sm text-[var(--ni-gray-light)]">
                    Last updated: {{ lastUpdated }}
                </p>
            </div>
        </div>
    </PublicLayout>
</template>
