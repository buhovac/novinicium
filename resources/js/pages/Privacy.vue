<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/layouts/PublicLayout.vue';

/**
 * Static legal page — content lives in the repo on purpose (public by
 * nature, rarely changes). Review the EDIT-ME items before publishing.
 */
const lastUpdated = 'July 2026'; // EDIT-ME: update when the policy changes
</script>

<template>
    <Head title="Privacy policy — Nov Inicium" />

    <PublicLayout>
        <div class="mx-auto max-w-2xl px-6 py-16">
            <h1 class="text-4xl font-bold">Privacy policy</h1>
            <p class="mt-3 text-lg text-[var(--ni-gray-light)]">
                What we collect, why, and what your rights are. The short
                version: we only process what you send us through the contact
                form, and we don't track you.
            </p>

            <div class="ni-prose mt-10 text-[var(--ni-gray)]">
                <section aria-labelledby="controller">
                    <h2
                        id="controller"
                        class="mt-10 mb-4 text-2xl font-semibold"
                    >
                        Who is responsible
                    </h2>
                    <p>
                        Nov Inicium SRL, a company registered in Belgium, is the
                        data controller for this website (novinicium.be). You
                        can reach us at
                        <a
                            href="mailto:contact@novinicium.be"
                            class="underline underline-offset-4"
                            >contact@novinicium.be</a
                        >.
                        <!-- EDIT-ME: potvrdi email; po želji dodaj BE broj poduzeća (BCE/KBO) -->
                    </p>
                </section>

                <section aria-labelledby="data">
                    <h2 id="data" class="mt-10 mb-4 text-2xl font-semibold">
                        What we collect
                    </h2>
                    <p>
                        <strong>Contact form.</strong> When you use the contact
                        form we receive the name, email address and message you
                        type in. That is the only personal data this site
                        collects deliberately.
                    </p>
                    <p>
                        <strong>Technical logs.</strong> Like almost every
                        website, our server keeps standard access logs (IP
                        address, requested page, browser type) for security and
                        troubleshooting. Logs are rotated automatically and are
                        not used to identify visitors.
                    </p>
                    <p>
                        <strong>Cookies.</strong> The site sets only functional
                        cookies that are strictly necessary for it to work (a
                        session cookie and a CSRF protection cookie). There are
                        no analytics, advertising or tracking cookies, and no
                        third-party trackers.
                    </p>
                </section>

                <section aria-labelledby="purpose">
                    <h2 id="purpose" class="mt-10 mb-4 text-2xl font-semibold">
                        Why we process it
                    </h2>
                    <p>
                        We use the contact form data for one purpose: to read
                        and reply to your message. The legal basis is our
                        legitimate interest in responding to enquiries, and —
                        where your message concerns a potential project — taking
                        steps prior to entering into a contract (GDPR art.
                        6(1)(f) and 6(1)(b)).
                    </p>
                </section>

                <section aria-labelledby="retention">
                    <h2
                        id="retention"
                        class="mt-10 mb-4 text-2xl font-semibold"
                    >
                        How long we keep it
                    </h2>
                    <p>
                        Contact messages are kept for as long as needed to
                        handle the enquiry and any follow-up, and no longer than
                        12 months after our last exchange, unless the
                        conversation leads to a contract (in which case
                        correspondence is kept as part of the business
                        relationship).
                        <!-- EDIT-ME: prilagodi rok ako želiš drugačiji -->
                    </p>
                </section>

                <section aria-labelledby="sharing">
                    <h2 id="sharing" class="mt-10 mb-4 text-2xl font-semibold">
                        Who we share it with
                    </h2>
                    <p>
                        We never sell personal data. Messages are stored on our
                        server hosted by Hetzner Online GmbH in the European
                        Union, and email replies are sent through our business
                        email provider. Both act as processors on our behalf. We
                        do not transfer your data outside of what is needed to
                        run this website and answer you.
                        <!-- EDIT-ME: ako odgovaraš kroz Google Workspace, možeš ga imenovati -->
                    </p>
                </section>

                <section aria-labelledby="rights">
                    <h2 id="rights" class="mt-10 mb-4 text-2xl font-semibold">
                        Your rights
                    </h2>
                    <p>
                        Under the GDPR you can ask us for access to the personal
                        data we hold about you, have it corrected or deleted,
                        restrict or object to its processing, and receive a copy
                        in a portable format. Email us at
                        <a
                            href="mailto:contact@novinicium.be"
                            class="underline underline-offset-4"
                            >contact@novinicium.be</a
                        >
                        and we will respond within one month.
                    </p>
                    <p>
                        If you believe we are handling your data unlawfully, you
                        also have the right to lodge a complaint with the
                        Belgian Data Protection Authority (<a
                            href="https://www.dataprotectionauthority.be"
                            class="underline underline-offset-4"
                            rel="noopener"
                            >dataprotectionauthority.be</a
                        >).
                    </p>
                </section>

                <p class="mt-12 text-sm text-[var(--ni-gray-light)]">
                    Last updated: {{ lastUpdated }}
                </p>
            </div>
        </div>
    </PublicLayout>
</template>
