<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <link rel="icon" href="/images/nov-inicium-favico.svg" sizes="any">
        <link rel="icon" href="/images/nov-inicium-favico.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @isset($robots)
            <meta name="robots" content="{{ $robots }}" data-ni-article-seo>
        @endisset

        {{-- Blog article metadata (BlogController::seo). Rendered here, not via
             Vue <Head>, so crawlers and link previews get it without SSR.
             Blog/Show.vue removes it when the visitor navigates away in-app. --}}
        @isset($seo)
            <meta name="description" content="{{ $seo['description'] }}" data-ni-article-seo>
            <link rel="canonical" href="{{ $seo['canonical'] }}" data-ni-article-seo>
            <meta property="og:type" content="article" data-ni-article-seo>
            <meta property="og:site_name" content="{{ $seo['site_name'] }}" data-ni-article-seo>
            <meta property="og:title" content="{{ $seo['title'] }}" data-ni-article-seo>
            <meta property="og:description" content="{{ $seo['description'] }}" data-ni-article-seo>
            <meta property="og:url" content="{{ $seo['canonical'] }}" data-ni-article-seo>
            @if ($seo['image'])
                <meta property="og:image" content="{{ $seo['image'] }}" data-ni-article-seo>
                <meta property="og:image:alt" content="{{ $seo['image_alt'] }}" data-ni-article-seo>
                <meta name="twitter:card" content="summary_large_image" data-ni-article-seo>
                <meta name="twitter:image:alt" content="{{ $seo['image_alt'] }}" data-ni-article-seo>
            @else
                <meta name="twitter:card" content="summary" data-ni-article-seo>
            @endif
            @if ($seo['published_time'])
                <meta property="article:published_time" content="{{ $seo['published_time'] }}" data-ni-article-seo>
            @endif
            @if ($seo['modified_time'])
                <meta property="article:modified_time" content="{{ $seo['modified_time'] }}" data-ni-article-seo>
            @endif
            <script type="application/ld+json" data-ni-article-seo>{!! json_encode($seo['json_ld'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        @endisset

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            {{-- Without SSR this is the title crawlers see; it mirrors Blog/Show.vue + app.ts. --}}
            @isset($seo)
                <title>{{ $seo['title'] }} — Nov Inicium Blog - {{ config('app.name', 'Laravel') }}</title>
            @else
                <title>{{ config('app.name', 'Laravel') }}</title>
            @endisset
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
