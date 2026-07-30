<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import '../../css/brand.css';

/** Social platforms: key in site settings → label + inline SVG path(s). */
const socialPlatforms = [
    {
        key: 'social_linkedin',
        label: 'LinkedIn',
        path: 'M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.28 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.75M6.88 8.56a1.68 1.68 0 0 0 1.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 0 0-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.37H5.5v8.37h2.77z',
    },
    {
        key: 'social_youtube',
        label: 'YouTube',
        path: 'M10 15l5.19-3L10 9v6m11.56-7.83c.13.47.22 1.1.28 1.9.07.8.1 1.49.1 2.09L22 12c0 2.19-.16 3.8-.44 4.83-.25.9-.83 1.48-1.73 1.73-.47.13-1.33.22-2.65.28-1.3.07-2.49.1-3.59.1L12 19c-4.19 0-6.8-.16-7.83-.44-.9-.25-1.48-.83-1.73-1.73-.13-.47-.22-1.1-.28-1.9-.07-.8-.1-1.49-.1-2.09L2 12c0-2.19.16-3.8.44-4.83.25-.9.83-1.48 1.73-1.73.47-.13 1.33-.22 2.65-.28 1.3-.07 2.49-.1 3.59-.1L12 5c4.19 0 6.8.16 7.83.44.9.25 1.48.83 1.73 1.73z',
    },
    {
        key: 'social_instagram',
        label: 'Instagram',
        path: 'M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85 0 3.2-.01 3.58-.07 4.85-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.64.07-4.85.07-3.2 0-3.58-.01-4.85-.07-3.26-.15-4.77-1.7-4.92-4.92-.06-1.27-.07-1.64-.07-4.85 0-3.2.01-3.58.07-4.85.15-3.23 1.66-4.77 4.92-4.92 1.27-.06 1.65-.07 4.85-.07M12 0C8.74 0 8.33.01 7.05.07 2.7.27.27 2.69.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c4.35-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.2-4.35-2.62-6.78-6.98-6.98C15.67.01 15.26 0 12 0m0 5.84a6.16 6.16 0 1 0 0 12.32 6.16 6.16 0 0 0 0-12.32M12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8m6.41-11.85a1.44 1.44 0 1 0 0 2.88 1.44 1.44 0 0 0 0-2.88z',
    },
    {
        key: 'social_x',
        label: 'X',
        path: 'M18.9 1.15h3.68l-8.04 9.19L24 22.85h-7.41l-5.8-7.58-6.64 7.58H.47l8.6-9.83L0 1.15h7.6l5.24 6.93 6.06-6.93m-1.29 19.5h2.04L6.49 3.24H4.3z',
    },
    {
        key: 'social_facebook',
        label: 'Facebook',
        path: 'M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07z',
    },
    {
        key: 'social_github',
        label: 'GitHub',
        path: 'M12 .3a12 12 0 0 0-3.8 23.4c.6.1.8-.3.8-.6v-2c-3.3.7-4-1.6-4-1.6-.6-1.4-1.4-1.8-1.4-1.8-1-.7.1-.7.1-.7 1.2.1 1.8 1.2 1.8 1.2 1 1.8 2.8 1.3 3.5 1 0-.8.4-1.3.7-1.6-2.7-.3-5.5-1.3-5.5-6 0-1.2.5-2.3 1.3-3.1-.2-.4-.6-1.6.1-3.2 0 0 1-.3 3.3 1.2a11.5 11.5 0 0 1 6 0c2.3-1.5 3.3-1.2 3.3-1.2.7 1.6.2 2.8.1 3.2.8.8 1.3 1.9 1.3 3.1 0 4.7-2.8 5.7-5.5 6 .4.4.8 1.1.8 2.2v3.3c0 .3.2.7.8.6A12 12 0 0 0 12 .3z',
    },
    {
        key: 'social_tiktok',
        label: 'TikTok',
        path: 'M16.6 5.82a4.28 4.28 0 0 1-1.06-2.82h-3.3v13.6a2.59 2.59 0 0 1-2.58 2.5 2.58 2.58 0 1 1 .8-5.04V10.7a5.87 5.87 0 0 0-1-.08 5.9 5.9 0 1 0 5.9 5.9V9.42a7.55 7.55 0 0 0 4.4 1.4V7.52a4.29 4.29 0 0 1-3.16-1.7z',
    },
    {
        key: 'social_bluesky',
        label: 'Bluesky',
        path: 'M5.2 3.24c2.75 2.06 5.7 6.25 6.8 8.5 1.1-2.25 4.05-6.44 6.8-8.5 1.98-1.49 5.2-2.64 5.2.98 0 .73-.42 6.08-.66 6.95-.85 3.02-3.93 3.8-6.66 3.33 4.78.82 6 3.5 3.37 6.19-4.99 5.09-7.17-1.28-7.73-2.91-.1-.3-.15-.44-.15-.32 0-.12-.05.02-.15.32-.56 1.63-2.74 8-7.73 2.91-2.63-2.68-1.41-5.37 3.37-6.19-2.73.47-5.81-.31-6.66-3.33C.75 10.3.33 4.95.33 4.22c0-3.62 3.22-2.47 5.2-.98z',
    },
] as const;

/** Only platforms that have a URL set in site settings, in fixed order. */
const activeSocials = computed(() =>
    socialPlatforms
        .map((p) => ({
            ...p,
            url: (site.value as Record<string, string>)[p.key],
        }))
        .filter((p) => !!p.url),
);

/** Address may contain \n line breaks; split for rendering. */
const addressLines = computed(() =>
    (site.value.footer_address ?? '')
        .split('\n')
        .filter((l) => l.trim() !== ''),
);

const currentYear = new Date().getFullYear();
interface SiteCopy {
    footer_text: string;
    footer_company_name: string;
    footer_address: string;
    footer_email: string;
    footer_phone: string;
    footer_vat: string;
    [key: string]: string; // allows social_* keys
}

const site = computed<SiteCopy>(() => ({
    footer_text: 'A short footer line about the studio.',
    footer_company_name: 'Example Studio',
    footer_address: '',
    footer_email: '',
    footer_phone: '',
    footer_vat: '',
    ...(page.props.site as Partial<SiteCopy> | undefined),
}));

const page = usePage();

const navItems = [
    { title: 'Home', href: '/' },
    { title: 'Portfolio', href: '/portfolio' },
    { title: 'Blog', href: '/blog' },
    { title: 'Contact', href: '/contact' },
];

const legalItems = [
    { title: 'Privacy', href: '/privacy' },
    { title: 'Accessibility', href: '/accessibility' },
];

/** aria-current="page" for the active section (exact for home, prefix otherwise). */
function isCurrent(href: string): boolean {
    const url = page.url;

    return href === '/' ? url === '/' : url.startsWith(href);
}

/** If the real (gitignored) logo isn't on disk, fall back to the repo placeholder. */
function onLogoError(event: Event, fallback: string): void {
    const img = event.target as HTMLImageElement;

    if (img.src.endsWith(fallback)) {
        return;
    } // avoid loop if placeholder also missing

    img.src = fallback;
}

// ---------- Mobile menu ----------
const mobileMenuOpen = ref(false);

// Close the panel after any Inertia navigation (link tapped, back button, …).
let removeNavigateListener: (() => void) | undefined;
onMounted(() => {
    removeNavigateListener = router.on('navigate', () => {
        mobileMenuOpen.value = false;
    });
});
onUnmounted(() => removeNavigateListener?.());
</script>

<template>
    <div class="ni-site min-h-screen bg-white text-[var(--ni-gray)]">
        <a href="#main" class="skip-link">Skip to main content</a>

        <header class="border-b border-[var(--ni-gray-lightest)]">
            <div
                class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-6 py-5"
            >
                <Link
                    href="/"
                    class="flex items-center gap-2.5"
                    aria-label="Nov Inicium — home"
                >
                    <img
                        src="/images/logo.svg"
                        alt="Nov Inicium"
                        class="h-9 w-auto"
                        @error="
                            onLogoError($event, '/images/logo-placeholder.svg')
                        "
                    />
                </Link>

                <!-- Desktop navigation -->
                <nav aria-label="Main navigation" class="hidden md:block">
                    <ul class="flex items-center gap-2">
                        <li v-for="item in navItems" :key="item.href">
                            <Link
                                :href="item.href"
                                :aria-current="
                                    isCurrent(item.href) ? 'page' : undefined
                                "
                                class="rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                                :class="
                                    isCurrent(item.href)
                                        ? 'bg-[var(--ni-green)] text-[var(--ni-gray)]'
                                        : 'text-[var(--ni-gray-light)] hover:bg-[var(--ni-gray-lightest)] hover:text-[var(--ni-gray)]'
                                "
                            >
                                {{ item.title }}
                            </Link>
                        </li>
                    </ul>
                </nav>

                <!-- Mobile menu toggle -->
                <button
                    type="button"
                    class="rounded-lg p-2 text-[var(--ni-gray)] hover:bg-[var(--ni-gray-lightest)] md:hidden"
                    :aria-expanded="mobileMenuOpen"
                    aria-controls="mobile-menu"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                >
                    <span class="sr-only">{{
                        mobileMenuOpen ? 'Close menu' : 'Open menu'
                    }}</span>
                    <!-- Hamburger / X -->
                    <svg
                        v-if="!mobileMenuOpen"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"
                        />
                    </svg>
                    <svg
                        v-else
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18 18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <!-- Mobile navigation panel -->
            <nav
                v-show="mobileMenuOpen"
                id="mobile-menu"
                aria-label="Main navigation"
                class="border-t border-[var(--ni-gray-lightest)] md:hidden"
            >
                <ul class="space-y-1 px-4 py-3">
                    <li v-for="item in navItems" :key="item.href">
                        <Link
                            :href="item.href"
                            :aria-current="
                                isCurrent(item.href) ? 'page' : undefined
                            "
                            class="block rounded-lg px-3 py-2.5 text-base font-medium transition-colors"
                            :class="
                                isCurrent(item.href)
                                    ? 'bg-[var(--ni-green)] text-[var(--ni-gray)]'
                                    : 'text-[var(--ni-gray-light)] hover:bg-[var(--ni-gray-lightest)] hover:text-[var(--ni-gray)]'
                            "
                        >
                            {{ item.title }}
                        </Link>
                    </li>
                </ul>
            </nav>
        </header>

        <main id="main" tabindex="-1" class="focus:outline-none">
            <slot />
        </main>

        <footer
            class="border-t-[3px] border-[var(--ni-green)] bg-[var(--ni-gray)] text-white"
        >
            <div class="mx-auto max-w-6xl px-6 py-12">
                <div class="grid gap-10 md:grid-cols-3">
                    <!-- Company -->
                    <div>
                        <img
                            src="/images/logo-light.svg"
                            alt="Nov Inicium"
                            class="h-8 w-auto"
                            @error="
                                onLogoError(
                                    $event,
                                    '/images/logo-light-placeholder.svg',
                                )
                            "
                        />
                        <p class="mt-3 text-sm text-[var(--ni-gray-lightest)]">
                            {{ site.footer_text }}
                        </p>
                        <address
                            class="mt-4 text-sm text-[var(--ni-gray-lightest)] not-italic"
                        >
                            <p class="font-semibold text-white">
                                {{ site.footer_company_name }}
                            </p>
                            <p
                                v-for="line in addressLines"
                                :key="line"
                                class="mt-0.5"
                            >
                                {{ line }}
                            </p>
                            <p v-if="site.footer_email" class="mt-2">
                                <a
                                    :href="`mailto:${site.footer_email}`"
                                    class="underline underline-offset-4 hover:text-[var(--ni-green)]"
                                >
                                    {{ site.footer_email }}
                                </a>
                            </p>
                            <p v-if="site.footer_phone">
                                <a
                                    :href="`tel:${site.footer_phone.replace(/\s/g, '')}`"
                                    class="underline underline-offset-4 hover:text-[var(--ni-green)]"
                                >
                                    {{ site.footer_phone }}
                                </a>
                            </p>
                            <p
                                v-if="site.footer_vat"
                                class="mt-2 text-xs text-[var(--ni-gray-light)]"
                            >
                                VAT:
                                <span class="text-[var(--ni-gray-lightest)]">{{
                                    site.footer_vat
                                }}</span>
                            </p>
                        </address>
                    </div>

                    <!-- Navigation -->
                    <nav aria-label="Footer navigation">
                        <h2
                            class="text-sm font-semibold tracking-wider text-[var(--ni-green)] uppercase"
                        >
                            Navigation
                        </h2>
                        <ul class="mt-4 space-y-2 text-sm">
                            <li v-for="item in navItems" :key="item.href">
                                <Link
                                    :href="item.href"
                                    class="text-[var(--ni-gray-lightest)] underline-offset-4 hover:text-[var(--ni-green)] hover:underline"
                                >
                                    {{ item.title }}
                                </Link>
                            </li>
                            <li v-for="item in legalItems" :key="item.href">
                                <Link
                                    :href="item.href"
                                    class="text-[var(--ni-gray-lightest)] underline-offset-4 hover:text-[var(--ni-green)] hover:underline"
                                >
                                    {{ item.title }}
                                </Link>
                            </li>
                        </ul>
                    </nav>

                    <!-- Social -->
                    <div v-if="activeSocials.length">
                        <h2
                            class="text-sm font-semibold tracking-wider text-[var(--ni-green)] uppercase"
                        >
                            Connect
                        </h2>
                        <ul class="mt-4 flex flex-wrap gap-3">
                            <li
                                v-for="social in activeSocials"
                                :key="social.key"
                            >
                                <a
                                    :href="social.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    :aria-label="`${social.label} (opens in a new tab)`"
                                    class="flex h-11 w-11 items-center justify-center rounded-full bg-[var(--ni-green)] transition-transform hover:-translate-y-0.5"
                                >
                                    <svg
                                        class="h-5 w-5 fill-[var(--ni-gray)]"
                                        viewBox="0 0 24 24"
                                        aria-hidden="true"
                                    >
                                        <path :d="social.path" />
                                    </svg>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="border-t border-white/10">
                <p
                    class="mx-auto max-w-6xl px-6 py-6 text-center text-xs text-[var(--ni-gray-lightest)]"
                >
                    &copy; {{ currentYear }} {{ site.footer_company_name }}. All
                    rights reserved.
                </p>
            </div>
        </footer>
    </div>
</template>
