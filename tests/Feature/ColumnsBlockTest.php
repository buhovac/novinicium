<?php

use App\Actions\Blog\BuildPostContent;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\DemoArticleSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

/**
 * @param  list<list<array<string, mixed>>>  $columns
 * @return array<string, mixed>
 */
function gridNode(array $columns, string $breakpoint = 'md'): array
{
    return [
        'type' => 'grid',
        'attrs' => ['data-cols' => (string) count($columns), 'data-from-breakpoint' => $breakpoint],
        'content' => array_map(fn (array $content): array => [
            'type' => 'gridColumn',
            'attrs' => ['data-col-span' => '1'],
            'content' => $content,
        ], $columns),
    ];
}

/**
 * @param  array<int, array<string, mixed>>  $content
 * @return list<array<string, mixed>>
 */
function normalizeColumns(array $content): array
{
    $post = Post::factory()->make(['body_json' => ['type' => 'doc', 'content' => $content]]);

    return app(BuildPostContent::class)->handle($post);
}

it('renders a responsive grid that keeps content order in the DOM', function () {
    $html = renderBlogContent(normalizeColumns([gridNode([
        [paragraphNode('First column text')],
        [customBlockNode('image', ['path' => 'images/zoom.png', 'decorative' => false, 'alt' => 'Second column image', 'width' => 'standard'])],
        [paragraphNode('Third column text')],
    ])]));

    expect($html)
        ->toContain('<div class="ni-grid" data-from="md" style="--ni-grid-cols:3;">')
        ->toContain('<div class="ni-grid-column" style="--ni-grid-span:1;">');

    // DOM order = authoring order = visual order.
    $first = strpos($html, 'First column text');
    $second = strpos($html, 'alt="Second column image"');
    $third = strpos($html, 'Third column text');

    expect($first)->toBeLessThan($second)
        ->and($second)->toBeLessThan($third);
});

it('never reorders columns visually with CSS', function () {
    $css = (string) file_get_contents(resource_path('css/brand.css'));
    preg_match_all('/[^{}]*\.ni-grid[^{]*\{[^}]*\}/', $css, $rules);

    expect($rules[0])->not->toBeEmpty();

    foreach ($rules[0] as $rule) {
        expect($rule)
            ->not->toMatch('/\border\s*:/')
            ->not->toContain('row-reverse')
            ->not->toContain('column-reverse')
            ->not->toContain('dense');
    }
});

it('stacks columns on narrow screens and only lays them out from the breakpoint', function () {
    $css = (string) file_get_contents(resource_path('css/brand.css'));

    // The base rule is a single-column grid; columns appear only inside media queries.
    expect($css)->toMatch('/\.ni-prose \.ni-grid \{\s*display: grid;\s*gap: [^;]+;\s*margin: [^;]+;\s*\}/')
        ->toMatch("/@media \\(min-width: 48rem\\) \\{\\s*\\.ni-grid\\[data-from='md'\\] \\{\\s*grid-template-columns/");
});

it('renders nested content through the same renderer', function () {
    $html = renderBlogContent(normalizeColumns([gridNode([
        [customBlockNode('callout', ['variant' => 'warning', 'title' => 'Nested callout', 'body' => 'Inside a column.'])],
        [customBlockNode('code', ['language' => 'php', 'code' => '<?php echo 1;'])],
    ])]));

    expect($html)
        ->toContain('<aside class="ni-callout" data-variant="warning"')
        ->toContain('Nested callout')
        ->toContain('&lt;?php echo 1;');
});

it('clamps column count and span and drops empty grids', function () {
    $nodes = normalizeColumns([
        ['type' => 'grid', 'attrs' => ['data-cols' => '0'], 'content' => [
            ['type' => 'gridColumn', 'attrs' => ['data-col-span' => '9'], 'content' => [paragraphNode('x')]],
        ]],
        ['type' => 'grid', 'attrs' => ['data-cols' => '2'], 'content' => []],
        paragraphNode('After'),
    ]);

    expect($nodes)->toHaveCount(2)
        ->and($nodes[0]['columns'])->toBe(1)
        ->and($nodes[0]['content'][0]['span'])->toBe(1);
});

it('round-trips the demo columns: seed, reopen, edit nested text, save, render', function () {
    $this->actingAs(User::factory()->create(['email' => 'admin@novinicium.be']));
    Filament::setCurrentPanel('admin');
    Storage::fake('public');
    Storage::disk('public')->put('images/cover.jpg', 'fake');

    $this->seed(DemoArticleSeeder::class);
    $post = Post::where('slug', DemoArticleSeeder::SLUG)->sole();
    $post->update(['image_path' => 'images/cover.jpg']);

    $component = Livewire::test(EditPost::class, ['record' => $post->getRouteKey()]);
    $document = $component->get('data.body_json');
    $gridIndex = collect($document['content'])->search(fn (array $node): bool => $node['type'] === 'grid');

    expect($document['content'][$gridIndex]['content'][1]['content'][0]['attrs']['id'])->toBe('image');

    $document['content'][$gridIndex]['content'][0]['content'][0]['content'][0]['text'] = 'Edited column text';

    $component
        ->fillForm(['body_json' => $document, 'published_at' => now()->subMinute()])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->get(route('blog.show', $post->refresh()))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('post.content', function ($content): bool {
            $grid = collect($content)->firstWhere('type', 'grid');

            return $grid['fromBreakpoint'] === 'md'
                && $grid['content'][0]['content'][0]['content'][0]['text'] === 'Edited column text'
                && $grid['content'][1]['content'][0]['type'] === 'image';
        }));
});
