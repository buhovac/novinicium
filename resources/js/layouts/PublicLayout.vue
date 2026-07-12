<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import '../../css/brand.css';

const page = usePage();

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
</script>

<template>
    <div class="ni-site min-h-screen bg-white text-[var(--ni-gray)]">
        <a href="#main" class="skip-link">Skip to main content</a>

        <header class="border-b border-[var(--ni-gray-lightest)]">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-6 py-5">
                <Link href="/" class="flex items-center gap-2.5" aria-label="Nov Inicium — home">
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-[var(--ni-green)] text-lg font-bold text-[var(--ni-gray)]"
                        aria-hidden="true"
                    >
                        NI
                    </span>
                    <span class="text-lg font-semibold tracking-tight">Nov Inicium</span>
                </Link>

                <nav aria-label="Main navigation">
                    <ul class="flex items-center gap-1 sm:gap-2">
                        <li v-for="item in navItems" :key="item.href">
                            <Link
                                :href="item.href"
                                :aria-current="isCurrent(item.href) ? 'page' : undefined"
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
            </div>
        </header>

        <main id="main" tabindex="-1" class="focus:outline-none">
            <slot />
        </main>

        <footer class="bg-dark mt-20 bg-[var(--ni-gray)] text-white">
            <div class="mx-auto flex max-w-6xl flex-col items-start justify-between gap-6 px-6 py-10 sm:flex-row sm:items-center">
                <div>
                    <p class="font-semibold">Nov Inicium</p>
                    <p class="mt-1 text-sm text-[var(--ni-gray-lightest)]">
                        SaaS products, tutorials and custom web development — novinicium.be
                    </p>
                </div>
                <nav aria-label="Footer navigation">
                    <ul class="flex flex-wrap gap-x-6 gap-y-2 text-sm">
                        <li v-for="item in navItems" :key="item.href">
                            <Link :href="item.href" class="on-dark underline underline-offset-4 hover:text-[var(--ni-green)]">
                                {{ item.title }}
                            </Link>
                        </li>
                    </ul>
                </nav>
            </div>
        </footer>
    </div>
</template>
