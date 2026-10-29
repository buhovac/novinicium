<?php

use App\Actions\Blog\ArticleOutline;
use App\Actions\Blog\BuildPostContent;
use Database\Seeders\DemoArticleSeeder;

/**
 * @return array<string, mixed>
 */
function headingNode(int $level, string $text): array
{
    return ['type' => 'heading', 'attrs' => ['level' => $level], 'content' => [['type' => 'text', 'text' => $text]]];
}

/**
 * @param  list<array<string, mixed>>  $content
 * @return list<array<string, mixed>>
 */
function outlinedNodes(array $content): array
{
    return app(BuildPostContent::class)->fromDocument(['type' => 'doc', 'content' => $content]);
}

/**
 * @param  list<array<string, mixed>>  $nodes
 * @return list<string>
 */
function headingIds(array $nodes): array
{
    return collect($nodes)->where('type', 'heading')->pluck('id')->values()->all();
}

it('derives heading ids from the heading text', function () {
    expect(headingIds(outlinedNodes([
        headingNode(2, 'What counts as a visual impairment?'),
        headingNode(3, 'Colour vision deficiency (CVD)'),
        headingNode(2, 'Über Kontrast & Größe'),
    ])))->toBe(['what-counts-as-a-visual-impairment', 'colour-vision-deficiency-cvd', 'uber-kontrast-grosse']);
});

it('deduplicates headings with the same text in document order', function () {
    expect(headingIds(outlinedNodes([
        headingNode(2, 'Examples'),
        headingNode(3, 'Examples'),
        headingNode(2, 'examples!'),
        headingNode(2, 'Examples 2'),
    ])))->toBe(['examples', 'examples-2', 'examples-3', 'examples-2-2']);
});

it('produces the same ids for the same content on every render', function () {
    $content = [headingNode(2, 'Intro'), paragraphNode('x'), headingNode(2, 'Intro'), headingNode(3, '')];

    expect(headingIds(outlinedNodes($content)))
        ->toBe(headingIds(outlinedNodes($content)))
        ->toBe(['intro', 'intro-2', 'section']);
});

it('never reuses ids owned by the page layout or by Vue', function () {
    expect(headingIds(outlinedNodes([headingNode(2, 'Main'), headingNode(2, 'V 0'), headingNode(2, 'App')])))
        ->toBe(['main-section', 'v-0-section', 'app-section']);
});

it('gives headings inside columns unique ids too', function () {
    $nodes = outlinedNodes([
        headingNode(2, 'Notes'),
        ['type' => 'grid', 'attrs' => ['data-cols' => 2, 'data-from-breakpoint' => 'md'], 'content' => [
            ['type' => 'gridColumn', 'attrs' => ['data-col-span' => 1], 'content' => [headingNode(3, 'Notes')]],
            ['type' => 'gridColumn', 'attrs' => ['data-col-span' => 1], 'content' => [paragraphNode('x')]],
        ]],
    ]);

    expect($nodes[1]['content'][0]['content'][0]['id'])->toBe('notes-2');
});

it('nests h3 entries under the preceding h2 in the table of contents', function () {
    $toc = app(ArticleOutline::class)->tableOfContents(outlinedNodes([
        headingNode(3, 'Before any section'),
        headingNode(2, 'Types'),
        headingNode(3, 'Low vision'),
        headingNode(3, 'Blindness'),
        headingNode(2, 'Causes'),
    ]));

    expect($toc)->toBe([
        ['id' => 'before-any-section', 'text' => 'Before any section', 'level' => 3, 'children' => []],
        ['id' => 'types', 'text' => 'Types', 'level' => 2, 'children' => [
            ['id' => 'low-vision', 'text' => 'Low vision', 'level' => 3, 'children' => []],
            ['id' => 'blindness', 'text' => 'Blindness', 'level' => 3, 'children' => []],
        ]],
        ['id' => 'causes', 'text' => 'Causes', 'level' => 2, 'children' => []],
    ]);
});

it('counts reading time from visible prose only, not code, image or chart data', function () {
    $outline = app(ArticleOutline::class);
    $words = fn (int $count): string => implode(' ', array_fill(0, $count, 'word'));

    $nodes = outlinedNodes([
        paragraphNode($words(150)),
        customBlockNode('callout', ['variant' => 'information', 'title' => 'Note', 'body' => $words(49)]),
        customBlockNode('code', ['language' => 'plain', 'code' => $words(1000)]),
        customBlockNode('image', ['path' => 'images/a.png', 'decorative' => false, 'alt' => $words(200), 'width' => 'standard']),
        customBlockNode('chart', [
            'title' => 'Chart', 'type' => 'bar', 'series_1' => 'S',
            'rows' => array_fill(0, 20, ['label' => 'long category label words here', 'value_1' => 1]),
        ]),
    ]);

    // 150 + "Note" + 49 + chart title "Chart" = 201 words → 2 minutes at 200 wpm.
    expect(count(preg_split('/\s+/', implode(' ', $outline->readableText($nodes)))))->toBe(201)
        ->and($outline->readingMinutes($nodes))->toBe(2)
        ->and($outline->readingMinutes([]))->toBe(1);
});

it('renders heading ids in the server markup and the table of contents links to them', function () {
    $nodes = outlinedNodes([headingNode(2, 'Types'), headingNode(3, 'Low vision'), headingNode(2, 'Types')]);

    $article = renderBlogContent($nodes);
    $toc = squish(renderBlogComponent('ArticleToc', ['entries' => app(ArticleOutline::class)->tableOfContents($nodes)]));

    expect($article)
        ->toContain('<h2 id="types">Types</h2>')
        ->toContain('<h3 id="low-vision">Low vision</h3>')
        ->toContain('<h2 id="types-2">Types</h2>')
        ->and($toc)
        ->toStartWith('<details class="ni-toc"><summary>On this page</summary><nav aria-label="Table of contents"><ol>')
        ->toContain('<li><a href="#types">Types</a><ol><li><a href="#low-vision">Low vision</a></li></ol></li><li><a href="#types-2">Types</a></li>');

    preg_match_all('/href="#([^"]+)"/', $toc, $anchors);
    preg_match_all('/ id="([^"]+)"/', $article, $ids);

    expect($anchors[1])->each->toBeIn($ids[1]);
});

it('gives the demo article unique ids for every heading and a two-level outline', function () {
    $nodes = outlinedNodes((new DemoArticleSeeder)->document()['content']);
    $toc = app(ArticleOutline::class)->tableOfContents($nodes);
    $ids = collect($toc)->flatMap(fn (array $entry): array => [$entry['id'], ...array_column($entry['children'], 'id')]);

    expect($ids->duplicates())->toBeEmpty()
        ->and($ids)->toContain('what-counts-as-a-visual-impairment', 'low-vision', 'assistive-technologies')
        ->and(collect($toc)->firstWhere('id', 'common-types')['children'])->not->toBeEmpty();
});
