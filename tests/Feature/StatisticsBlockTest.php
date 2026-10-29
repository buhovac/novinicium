<?php

use App\Actions\Blog\BuildPostContent;
use App\Filament\Resources\Posts\Blocks\StatisticsBlock;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\DemoArticleSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function statItem(array $overrides = []): array
{
    return ['value' => '2.2 billion', 'unit' => null, 'label' => 'people have a vision impairment', 'explanation' => null, 'source' => 'WHO (2019)', ...$overrides];
}

/**
 * @param  array<string, mixed>|string|null  $config
 * @return array<string, mixed>|null
 */
function normalizedStatistics(array|string|null $config): ?array
{
    $post = Post::factory()->make(['body_json' => ['type' => 'doc', 'content' => [customBlockNode('statistics', $config)]]]);

    return app(BuildPostContent::class)->handle($post)[0] ?? null;
}

it('normalizes valid statistics, including repeater uuid keys', function () {
    expect(normalizedStatistics(['layout' => 'two', 'items' => [
        'a1b2c3' => statItem(),
        'd4e5f6' => statItem(['value' => ' 4.5 ', 'unit' => ':1', 'label' => 'minimum contrast', 'explanation' => 'For normal text.', 'source' => '']),
    ]]))->toBe([
        'type' => 'statistics',
        'layout' => 'two',
        'items' => [
            ['value' => '2.2 billion', 'unit' => null, 'label' => 'people have a vision impairment', 'explanation' => null, 'source' => 'WHO (2019)'],
            ['value' => '4.5', 'unit' => ':1', 'label' => 'minimum contrast', 'explanation' => 'For normal text.', 'source' => null],
        ],
    ]);
});

it('renders value and label as text in reading order, inside a list', function () {
    $html = renderBlogContent([normalizedStatistics(['layout' => 'three', 'items' => [
        statItem(),
        statItem(['value' => '4.5', 'unit' => ':1', 'label' => 'minimum contrast', 'explanation' => 'Large text needs 3:1.', 'source' => null]),
    ]])]);

    expect($html)->toContain('<ul class="ni-stats" role="list" data-layout="three">');

    // Block ends separate text, as they do for screen readers.
    $text = trim((string) preg_replace('/\s+/', ' ', strip_tags((string) preg_replace('/<\/(p|li)>/', ' </$1>', $html))));

    expect($text)->toBe('2.2 billion people have a vision impairment Source: WHO (2019) 4.5:1 minimum contrast Large text needs 3:1.');
});

it('drops items without a label or value and keeps the valid ones', function () {
    $node = normalizedStatistics(['layout' => 'three', 'items' => [
        statItem(['label' => '']),
        statItem(['value' => '   ']),
        'not-an-item',
        statItem(['label' => 'valid']),
    ]]);

    expect($node['items'])->toHaveCount(1)
        ->and($node['items'][0]['label'])->toBe('valid');
});

it('drops the block when no valid statistic remains, without breaking the article', function (array|string|null $config) {
    $post = postWithBody([paragraphNode('Before'), customBlockNode('statistics', $config), paragraphNode('After')]);

    expect(app(BuildPostContent::class)->handle($post))->toHaveCount(2);
})->with([
    'null' => [null],
    'empty' => [[]],
    'no items' => [['layout' => 'single', 'items' => []]],
    'only invalid items' => [['layout' => 'single', 'items' => [['value' => '1'], ['label' => 'x']]]],
]);

it('supports single, two and three column layouts and falls back by item count', function (mixed $layout, int $count, string $expected) {
    $node = normalizedStatistics(['layout' => $layout, 'items' => array_fill(0, $count, statItem())]);

    expect($node['layout'])->toBe($expected);
})->with([
    'single' => ['single', 1, 'single'],
    'two' => ['two', 2, 'two'],
    'three' => ['three', 3, 'three'],
    'unknown with 1 item' => ['grid', 1, 'single'],
    'unknown with 2 items' => [null, 2, 'two'],
    'unknown with 3 items' => ['five', 3, 'three'],
]);

it('caps the group at three statistics', function () {
    expect(normalizedStatistics(['layout' => 'three', 'items' => array_fill(0, 5, statItem())])['items'])->toHaveCount(3);
});

it('writes the statistics into the html snapshot', function () {
    $post = postWithBody([customBlockNode('statistics', ['layout' => 'single', 'items' => [statItem()]])]);

    expect($post->body)->toContain('<li><strong>2.2 billion</strong> people have a vision impairment — Source: WHO (2019)</li>');
});

it('shows a preview card in the editor', function () {
    expect(StatisticsBlock::getPreviewLabel(['layout' => 'two', 'items' => [statItem(), statItem()]]))->toBe('Statistics — 2 figures')
        ->and(StatisticsBlock::getPreviewLabel(['items' => []]))->toBe('Statistics (incomplete)')
        ->and(StatisticsBlock::toPreviewHtml(['layout' => 'single', 'items' => [statItem(['label' => '<b>x</b>'])]]))->toContain('&lt;b&gt;x&lt;/b&gt;');
});

describe('filament editor', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->create(['email' => 'admin@novinicium.be']));
        Filament::setCurrentPanel('admin');
        Storage::fake('public');
        Storage::disk('public')->put('images/cover.jpg', 'fake');
    });

    it('requires a label for every statistic in the form', function () {
        $post = Post::factory()->create(['image_path' => 'images/cover.jpg']);

        $component = Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->callAction(
                TestAction::make('customBlock')
                    ->schemaComponent('body_json')
                    ->arguments(['id' => 'statistics', 'mode' => 'insert', 'editorSelection' => ['type' => 'text', 'anchor' => 1, 'head' => 1]]),
                data: ['layout' => 'single', 'items' => [['value' => '8', 'label' => '']]],
            );

        // (The repeater's empty default item adds its own errors under a UUID key.)
        $errorKeys = array_keys($component->errors()->toArray());

        expect($errorKeys)->toContain('mountedActions.0.data.items.0.label')
            ->not->toContain('mountedActions.0.data.items.0.value');
    });

    it('round-trips the demo statistics: seed, reopen, edit, save, render', function () {
        $this->seed(DemoArticleSeeder::class);
        $post = Post::where('slug', DemoArticleSeeder::SLUG)->sole();
        $post->update(['image_path' => 'images/cover.jpg']);

        $component = Livewire::test(EditPost::class, ['record' => $post->getRouteKey()]);
        $document = $component->get('data.body_json');
        $index = collect($document['content'])->search(fn (array $node): bool => ($node['attrs']['id'] ?? null) === 'statistics');

        expect($document['content'][$index]['attrs']['config']['items'])->toHaveCount(3);

        $document['content'][$index]['attrs']['config']['items'][0]['value'] = 'At least 2.2 billion';

        $component
            ->fillForm(['body_json' => $document, 'published_at' => now()->subMinute()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get(route('blog.show', $post->refresh()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('post.content', function ($content): bool {
                $statistics = collect($content)->firstWhere('type', 'statistics');

                return $statistics['layout'] === 'three'
                    && $statistics['items'][0]['value'] === 'At least 2.2 billion'
                    && $statistics['items'][2]['unit'] === ':1';
            }));
    });
});
