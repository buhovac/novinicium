<?php

/**
 * WCAG relative-luminance contrast ratio between two #RRGGBB colours.
 */
function contrastRatio(string $first, string $second): float
{
    $luminance = function (string $hex): float {
        $channels = array_map(
            fn (string $pair): float => hexdec($pair) / 255,
            str_split(ltrim($hex, '#'), 2),
        );
        $linear = array_map(
            fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4,
            $channels,
        );

        return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
    };

    $lighter = max($luminance($first), $luminance($second));
    $darker = min($luminance($first), $luminance($second));

    return ($lighter + 0.05) / ($darker + 0.05);
}

function brandCss(): string
{
    return (string) file_get_contents(resource_path('css/brand.css'));
}

it('draws every focus ring from the surface-aware --ni-focus-ring token', function () {
    expect(brandCss())
        ->toMatch('/:focus-visible\s*\{\s*outline:\s*3px solid var\(--ni-focus-ring\);\s*outline-offset:\s*2px;/')
        ->toMatch('/\.ni-site,\s*\.ni-site \.bg-light\s*\{\s*--ni-focus-ring:\s*var\(--ni-gray\);/')
        ->toMatch('/\.ni-site \.bg-dark,\s*\.ni-site \.on-dark\s*\{\s*--ni-focus-ring:\s*var\(--ni-green\);/')
        ->not->toContain('outline: 3px solid var(--ni-gray)');
});

it('gives the ring at least 3:1 against every surface it can sit on', function (string $ring, string $surface) {
    expect(contrastRatio($ring, $surface))->toBeGreaterThanOrEqual(3.0);
})->with([
    'dark ring on white' => ['#353535', '#ffffff'],
    'dark ring on lime' => ['#353535', '#beef35'],
    'dark ring on light lime' => ['#353535', '#d4f07e'],
    'dark ring on light gray (warning callout, code bar)' => ['#353535', '#e5e5e5'],
    'lime ring on dark (footer, CTA band)' => ['#beef35', '#353535'],
]);

it('marks the dark footer and dark CTA band as dark focus surfaces', function () {
    $layout = (string) file_get_contents(resource_path('js/layouts/PublicLayout.vue'));
    $home = (string) file_get_contents(resource_path('js/pages/Home.vue'));

    expect($layout)->toMatch('/<footer class="[^"]*\bbg-dark\b[^"]*bg-\[var\(--ni-gray\)\]/')
        ->and($home)->toMatch('/class="bg-dark bg-\[var\(--ni-gray\)\]"/');
});
