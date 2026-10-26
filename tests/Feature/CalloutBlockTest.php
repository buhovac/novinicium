<?php

use App\Actions\Blog\BuildPostContent;
use App\Filament\Resources\Posts\Blocks\CalloutBlock;
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
 * @param  array<string, mixed>|string|null  $config
 * @return array<string, mixed>
 */
function calloutNode(array|string|null $config): array
{
    return ['type' => 'customBlock', 'attrs' => ['id' => 'callout', 'config' => $config]];
}

/**
 * @return array<string, mixed>
 */
function paragraphNode(string $text): array
{
    return ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]];
}

/**
 * @param  array<int, array<string, mixed>>  $content
 */
function postWithBody(array $content): Post
{
    return Post::factory()->create(['body_json' => ['type' => 'doc', 'content' => $content]]);
}

it('renders a valid callout with its variant as a text label', function () {
    $post = postWithBody([
        calloutNode(['variant' => 'warning', 'title' => 'Check contrast', 'body' => "First.\n\nSecond."]),
    ]);

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('post.content', [[
            'type' => 'callout',
            'variant' => 'warning',
            'label' => 'Warning',
            'title' => 'Check contrast',
            'paragraphs' => ['First.', 'Second.'],
        ]]));
});

it('drops malformed callouts without breaking the rest of the article', function (array|string|null $config) {
    $post = postWithBody([
        paragraphNode('Before'),
        calloutNode($config),
        paragraphNode('After'),
    ]);

    expect(app(BuildPostContent::class)->handle($post))
        ->toHaveCount(2)
        ->sequence(
            fn ($node) => $node->content->{0}->text->toBe('Before'),
            fn ($node) => $node->content->{0}->text->toBe('After'),
        );
})->with([
    'null config' => [null],
    'string config' => ['not-an-array'],
    'empty config' => [[]],
    'missing title' => [['variant' => 'warning', 'body' => 'Body']],
    'blank title' => [['variant' => 'warning', 'title' => '   ', 'body' => 'Body']],
    'missing body' => [['variant' => 'warning', 'title' => 'Title']],
    'title not a string' => [['variant' => 'warning', 'title' => ['x'], 'body' => 'Body']],
    'body too long' => [['variant' => 'warning', 'title' => 'Title', 'body' => str_repeat('a', CalloutBlock::BODY_MAX_LENGTH + 1)]],
]);

it('drops custom blocks with an unknown id', function () {
    $post = postWithBody([
        ['type' => 'customBlock', 'attrs' => ['id' => 'not-a-block', 'config' => ['title' => 'x']]],
        paragraphNode('Kept'),
    ]);

    expect(app(BuildPostContent::class)->handle($post))->toHaveCount(1);
});

it('falls back to the information variant for an unknown variant', function (mixed $variant) {
    $post = postWithBody([
        calloutNode(['variant' => $variant, 'title' => 'Title', 'body' => 'Body']),
    ]);

    expect(app(BuildPostContent::class)->handle($post)[0])
        ->variant->toBe('information')
        ->label->toBe('Information');
})->with(['unknown string' => 'danger', 'missing' => null, 'array' => [['x']]]);

it('keeps html in callout text inert', function () {
    $post = postWithBody([
        calloutNode(['variant' => 'example', 'title' => '<img src=x onerror=alert(1)>', 'body' => '<script>alert(1)</script>']),
    ]);

    $node = app(BuildPostContent::class)->handle($post)[0];

    // Plain strings for {{ }} interpolation in Vue: shown as text, never parsed.
    expect($node['title'])->toBe('<img src=x onerror=alert(1)>')
        ->and($node['paragraphs'])->toBe(['<script>alert(1)</script>']);

    // The HTML snapshot escapes it as well.
    expect($post->fresh()->body)
        ->toContain('&lt;script&gt;')
        ->not->toContain('<script>')
        ->not->toContain('<img');
});

it('writes the callout into the html snapshot', function () {
    $post = postWithBody([
        calloutNode(['variant' => 'important', 'title' => 'Back up', 'body' => 'Do it daily.']),
    ]);

    expect($post->body)
        ->toContain('<aside>')
        ->toContain('Important: Back up')
        ->toContain('<p>Do it daily.</p>');
});

it('shows a preview card in the editor, not a full render', function () {
    $config = ['variant' => 'definition', 'title' => 'Low vision', 'body' => 'Some usable sight.'];

    expect(CalloutBlock::getPreviewLabel($config))->toBe('Callout — Definition: Low vision')
        ->and(CalloutBlock::toPreviewHtml($config))->toBe('<p><strong>Definition:</strong> Low vision</p><p>Some usable sight.</p>')
        ->and(CalloutBlock::getPreviewLabel([]))->toBe('Callout (incomplete)');
});

describe('filament editor', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->create(['email' => 'admin@novinicium.be']));
        Filament::setCurrentPanel('admin');
        Storage::fake('public');
        Storage::disk('public')->put('images/cover.jpg', 'fake');
    });

    it('reopens a post with its callout config intact and saves it unchanged', function () {
        $config = ['variant' => 'warning', 'title' => 'Mind the gap', 'body' => "One.\n\nTwo."];
        $post = Post::factory()->create([
            'image_path' => 'images/cover.jpg',
            'body_json' => ['type' => 'doc', 'content' => [calloutNode($config), paragraphNode('Text')]],
        ]);

        Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->assertSchemaStateSet(function (array $state) use ($config): bool {
                $block = $state['body_json']['content'][0];

                return $block['type'] === 'customBlock'
                    && $block['attrs']['id'] === 'callout'
                    && $block['attrs']['config'] === $config;
            })
            ->call('save')
            ->assertHasNoFormErrors();

        expect($post->refresh()->body_json['content'][0]['attrs']['config'])->toBe($config)
            ->and(app(BuildPostContent::class)->handle($post)[0]['title'])->toBe('Mind the gap');
    });

    it('round-trips the demo article: seed, reopen, edit, save, render', function () {
        $this->seed(DemoArticleSeeder::class);
        $post = Post::where('slug', DemoArticleSeeder::SLUG)->sole();
        $post->update(['image_path' => 'images/cover.jpg']);

        $component = Livewire::test(EditPost::class, ['record' => $post->getRouteKey()]);

        $document = $component->get('data.body_json');
        $warningIndex = collect($document['content'])
            ->search(fn (array $node): bool => ($node['attrs']['config']['variant'] ?? null) === 'warning');
        $document['content'][$warningIndex]['attrs']['config']['title'] = 'Edited warning title';

        $component
            ->fillForm(['body_json' => $document, 'published_at' => now()->subMinute()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get(route('blog.show', $post->refresh()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where(
                'post.content',
                fn ($content): bool => collect($content)->where('type', 'callout')->count() === 5
                    && collect($content)->firstWhere('variant', 'warning')['title'] === 'Edited warning title',
            ));
    });

    it('validates the callout modal form', function () {
        $post = Post::factory()->create(['image_path' => 'images/cover.jpg']);

        Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->callAction(
                TestAction::make('customBlock')
                    ->schemaComponent('body_json')
                    ->arguments(['id' => 'callout', 'mode' => 'insert', 'editorSelection' => ['type' => 'text', 'anchor' => 1, 'head' => 1]]),
                data: ['variant' => null, 'title' => '', 'body' => ''],
            )
            ->assertHasActionErrors(['variant' => 'required', 'title' => 'required', 'body' => 'required']);
    });
});

describe('demo article', function () {
    it('seeds a draft demo article with one callout of each variant', function () {
        $this->seed(DemoArticleSeeder::class);

        $post = Post::where('slug', DemoArticleSeeder::SLUG)->sole();
        $callouts = collect(app(BuildPostContent::class)->handle($post))->where('type', 'callout');

        expect($post->published_at)->toBeNull()
            ->and($callouts->pluck('variant')->sort()->values()->all())
            ->toBe(['definition', 'example', 'important', 'information', 'warning']);

        $this->get(route('blog.show', $post))->assertNotFound();
    });

    it('refuses to run in production', function () {
        app()->detectEnvironment(fn () => 'production');

        expect(fn () => (new DemoArticleSeeder)->run())->toThrow(RuntimeException::class);
        expect(Post::where('slug', DemoArticleSeeder::SLUG)->exists())->toBeFalse();
    });
});
