<?php

use App\Actions\Blog\BuildPostContent;
use App\Filament\Resources\Posts\Blocks\TableCaptionBlock;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\DemoArticleSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

/**
 * @return array<string, mixed>
 */
function nativeTable(): array
{
    $cell = fn (string $type, string $text): array => ['type' => $type, 'content' => [paragraphNode($text)]];

    return ['type' => 'table', 'content' => [
        ['type' => 'tableRow', 'content' => [$cell('tableHeader', 'Barrier'), $cell('tableHeader', 'Adaptation')]],
        ['type' => 'tableRow', 'content' => [$cell('tableHeader', 'Low contrast'), $cell('tableCell', 'Use 4.5:1')]],
    ]];
}

/**
 * @param  array<int, array<string, mixed>>  $content
 * @return list<array<string, mixed>>
 */
function normalizeContent(array $content): array
{
    $post = Post::factory()->make(['body_json' => ['type' => 'doc', 'content' => $content]]);

    return app(BuildPostContent::class)->handle($post);
}

it('attaches a caption block to the table directly below it', function () {
    $nodes = normalizeContent([customBlockNode('tableCaption', ['caption' => '  Barriers  ']), nativeTable()]);

    expect($nodes)->toHaveCount(1)
        ->and($nodes[0]['type'])->toBe('table')
        ->and($nodes[0]['caption'])->toBe('Barriers');
});

it('leaves tables without a caption block uncaptioned', function () {
    expect(normalizeContent([nativeTable()])[0]['caption'])->toBeNull();
});

it('drops a caption that is not directly above a table', function () {
    $nodes = normalizeContent([
        customBlockNode('tableCaption', ['caption' => 'Orphan']),
        paragraphNode('In between'),
        nativeTable(),
    ]);

    expect(array_column($nodes, 'type'))->toBe(['paragraph', 'table'])
        ->and($nodes[1]['caption'])->toBeNull();
});

it('attaches captions inside nested content such as columns', function () {
    $nodes = normalizeContent([[
        'type' => 'grid',
        'attrs' => ['data-cols' => '1'],
        'content' => [['type' => 'gridColumn', 'content' => [customBlockNode('tableCaption', ['caption' => 'Nested']), nativeTable()]]],
    ]]);

    expect($nodes[0]['content'][0]['content'][0]['caption'])->toBe('Nested');
});

it('drops a malformed caption but keeps the table', function (array|string|null $config) {
    $nodes = normalizeContent([customBlockNode('tableCaption', $config), nativeTable()]);

    expect($nodes)->toHaveCount(1)
        ->and($nodes[0]['caption'])->toBeNull();
})->with(['null' => [null], 'empty' => [[]], 'blank' => [['caption' => '  ']], 'too long' => [['caption' => str_repeat('a', TableCaptionBlock::CAPTION_MAX_LENGTH + 1)]]]);

it('drops a table without rows without breaking the article', function () {
    expect(array_column(normalizeContent([paragraphNode('A'), ['type' => 'table', 'content' => []], paragraphNode('B')]), 'type'))
        ->toBe(['paragraph', 'paragraph']);
});

it('renders a captioned table inside a named, focusable scroll region', function () {
    $html = renderBlogContent(normalizeContent([customBlockNode('tableCaption', ['caption' => 'Barriers']), nativeTable()]));

    preg_match('/<caption id="([^"]+)">\s*Barriers\s*<\/caption>/', $html, $caption);

    expect($caption)->not->toBeEmpty()
        ->and($html)->toContain('<div class="ni-table" role="region" tabindex="0" aria-labelledby="'.$caption[1].'">')
        ->toContain('<thead><tr><th scope="col">')
        ->toContain('<th scope="row"><p>Low contrast</p></th><td><p>Use 4.5:1</p></td>')
        ->not->toContain('<img');
});

it('renders an uncaptioned table with a generic region label and no caption', function () {
    $html = renderBlogContent(normalizeContent([nativeTable()]));

    expect($html)->toContain('<div class="ni-table" role="region" tabindex="0" aria-label="Table">')
        ->not->toContain('<caption');
});

it('shows a preview card in the editor', function () {
    expect(TableCaptionBlock::getPreviewLabel(['caption' => 'Barriers']))->toBe('Table caption — Barriers')
        ->and(TableCaptionBlock::toPreviewHtml(['caption' => '<b>x</b>']))->toContain('&lt;b&gt;x&lt;/b&gt;')->toContain('table directly below');
});

it('round-trips the demo table: seed, reopen, edit caption and a cell, save, render', function () {
    $this->actingAs(User::factory()->create(['email' => 'admin@novinicium.be']));
    Filament::setCurrentPanel('admin');
    Storage::fake('public');
    Storage::disk('public')->put('images/cover.jpg', 'fake');

    $this->seed(DemoArticleSeeder::class);
    $post = Post::where('slug', DemoArticleSeeder::SLUG)->sole();
    $post->update(['image_path' => 'images/cover.jpg']);

    $component = Livewire::test(EditPost::class, ['record' => $post->getRouteKey()]);
    $document = $component->get('data.body_json');
    $captionIndex = collect($document['content'])->search(fn (array $node): bool => ($node['attrs']['id'] ?? null) === 'tableCaption');

    expect($document['content'][$captionIndex + 1]['type'])->toBe('table');

    $document['content'][$captionIndex]['attrs']['config']['caption'] = 'Edited caption';
    $document['content'][$captionIndex + 1]['content'][1]['content'][2]['content'][0]['content'][0]['text'] = 'Edited cell';

    $component
        ->fillForm(['body_json' => $document, 'published_at' => now()->subMinute()])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->get(route('blog.show', $post->refresh()))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('post.content', function ($content): bool {
            $table = collect($content)->firstWhere('type', 'table');

            return $table['caption'] === 'Edited caption'
                && $table['content'][1]['content'][2]['content'][0]['content'][0]['text'] === 'Edited cell'
                && collect($content)->where('type', 'tableCaption')->isEmpty();
        }));
});
