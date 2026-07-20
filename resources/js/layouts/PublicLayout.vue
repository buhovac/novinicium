<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import '../../css/brand.css';

interface SiteCopy {
    footer_title: string;
    footer_text: string;
}

const page = usePage();

const site = computed<SiteCopy>(() => ({
    footer_title: 'Example Studio',
    footer_text: 'A short footer line about the studio.',
    ...(page.props.site as Partial<SiteCopy> | undefined),
}));

const navItems = [
    { title: 'Home', href: '/' },
    { title: 'Portfolio', href: '/portfolio' },
    { title: 'Blog', href: '/blog' },
    { title: 'Contact', href: '/contact' },
];

/** aria-current="page" for the active section (exact for home, prefix otherwise). */
function isCurrent(href: string): boolean {
    const url = page.url;

    return href === '/' ? url === '/' : url.startsWith(href);
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
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-[var(--ni-green)] text-lg font-bold text-[var(--ni-gray)]"
                        aria-hidden="true"
                    >
                        NI
                    </span>
                    <span class="text-lg font-semibold tracking-tight"
                        >Nov Inicium</span
                    >
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

        <footer class="bg-dark mt-20 bg-[var(--ni-gray)] text-white">
            <div
                class="mx-auto flex max-w-6xl flex-col items-start justify-between gap-6 px-6 py-10 sm:flex-row sm:items-center"
            >
                <div>
                    <p class="font-semibold">{{ site.footer_title }}</p>
                    <p class="mt-1 text-sm text-[var(--ni-gray-lightest)]">
                        {{ site.footer_text }}
                    </p>
                </div>
                <nav aria-label="Footer navigation">
                    <ul class="flex flex-wrap gap-x-6 gap-y-2 text-sm">
                        <li v-for="item in navItems" :key="item.href">
                            <Link
                                :href="item.href"
                                class="on-dark underline underline-offset-4 hover:text-[var(--ni-green)]"
                            >
                                {{ item.title }}
                            </Link>
                        </li>
                    </ul>
                </nav>
            </div>
        </footer>
    </div>
</template>
