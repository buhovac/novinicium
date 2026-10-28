<?php

use App\Actions\Blog\BuildPostContent;
use App\Filament\Resources\Posts\Blocks\ImageBlock;
use App\Filament\Resources\Posts\Blocks\ImageComparisonBlock;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\DemoArticleSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function comparisonConfig(array $overrides = []): array
{
    return [
        'first_path' => 'images/normal.png',
        'first_alt' => 'An eye chart, sharp and clear',
        'first_label' => 'Normal vision',
        'second_path' => 'images/cataract.png',
        'second_alt' => 'The same eye chart, blurred and yellowed',
        'second_label' => 'Cataract',
        'caption' => 'A simulation for illustration only.',
        ...$overrides,
    ];
}

/**
 * @param  array<string, mixed>|string|null  $config
 * @return array<string, mixed>|null
 */
function normalizedComparison(array|string|null $config): ?array
{
    $post = Post::factory()->make(['body_json' => ['type' => 'doc', 'content' => [customBlockNode('imageComparison', $config)]]]);

    return app(BuildPostContent::class)->handle($post)[0] ?? null;
}

it('normalizes a valid comparison into a typed node', function () {
    expect(normalizedComparison(comparisonConfig()))->toBe([
        'type' => 'imageComparison',
        'first' => ['src' => '/storage/images/normal.png', 'alt' => 'An eye chart, sharp and clear', 'label' => 'Normal vision'],
        'second' => ['src' => '/storage/images/cataract.png', 'alt' => 'The same eye chart, blurred and yellowed', 'label' => 'Cataract'],
        'caption' => 'A simulation for illustration only.',
    ]);
});

it('resolves both paths through Post::storageUrlFor()', function () {
    $node = normalizedComparison(comparisonConfig());

    expect($node['first']['src'])->toBe(Post::storageUrlFor('images/normal.png'))
        ->and($node['second']['src'])->toBe(Post::storageUrlFor('images/cataract.png'));
});

it('treats labels and caption as optional', function () {
    $node = normalizedComparison(comparisonConfig(['first_label' => '', 'second_label' => '  ', 'caption' => null]));

    expect($node['first']['label'])->toBeNull()
        ->and($node['second']['label'])->toBeNull()
        ->and($node['caption'])->toBeNull();
});

it('drops the block when either alt text is missing', function (string $field, mixed $alt) {
    expect(normalizedComparison(comparisonConfig([$field => $alt])))->toBeNull();
})->with([
    'first missing' => ['first_alt', null],
    'first whitespace' => ['first_alt', '   '],
    'second missing' => ['second_alt', null],
    'second empty' => ['second_alt', ''],
    'second not a string' => ['second_alt', ['x']],
]);

it('only accepts uploaded raster images from the images folder', function (string $field, string $path) {
    expect(normalizedComparison(comparisonConfig([$field => $path])))->toBeNull();
})->with([
    'external url' => ['first_path', 'https://evil.example/x.png'],
    'javascript' => ['second_path', 'javascript:alert(1)'],
    'path traversal' => ['first_path', 'images/../.env'],
    'svg' => ['second_path', 'images/x.svg'],
]);

it('drops malformed comparison blocks without breaking the article', function (array|string|null $config) {
    $post = postWithBody([paragraphNode('Before'), customBlockNode('imageComparison', $config), paragraphNode('After')]);

    expect(app(BuildPostContent::class)->handle($post))->toHaveCount(2);
})->with([
    'null' => [null],
    'string' => ['x'],
    'empty' => [[]],
    'one image only' => [['first_path' => 'images/a.png', 'first_alt' => 'A']],
]);

it('writes both images and the caption into the html snapshot', function () {
    $post = postWithBody([customBlockNode('imageComparison', comparisonConfig())]);

    expect($post->body)
        ->toContain('alt="An eye chart, sharp and clear"')
        ->toContain('alt="The same eye chart, blurred and yellowed"')
        ->toContain('<figcaption>Normal vision / Cataract — A simulation for illustration only.</figcaption>');
});

it('shows a preview card in the editor', function () {
    expect(ImageComparisonBlock::getPreviewLabel(comparisonConfig()))->toBe('Image comparison — Normal vision / Cataract')
        ->and(ImageComparisonBlock::getPreviewLabel(comparisonConfig(['second_alt' => ''])))->toBe('Image comparison (incomplete)')
        ->and(ImageComparisonBlock::toPreviewHtml(comparisonConfig(['first_label' => '<b>x</b>'])))->toContain('&lt;b&gt;x&lt;/b&gt;');
});

describe('rendered markup', function () {
    it('renders the side-by-side baseline with both images, alts, labels and caption', function () {
        $html = squish(renderBlogContent([normalizedComparison(comparisonConfig())]));

        expect($html)
            ->toContain('<figure class="ni-compare"><div class="ni-compare-grid">')
            ->toContain('<figure class="ni-compare-item"><img src="/storage/images/normal.png" alt="An eye chart, sharp and clear" loading="lazy" decoding="async"><figcaption>Normal vision</figcaption></figure>')
            ->toContain('<figure class="ni-compare-item"><img src="/storage/images/cataract.png" alt="The same eye chart, blurred and yellowed" loading="lazy" decoding="async"><figcaption>Cataract</figcaption></figure>')
            ->toContain('</div><figcaption class="ni-compare-caption">A simulation for illustration only.</figcaption></figure>');
    });

    it('is fully perceivable without interaction: no slider or view switch in the server markup', function () {
        $html = renderBlogContent([normalizedComparison(comparisonConfig())]);

        expect($html)
            ->not->toContain('type="range"')
            ->not->toContain('ni-compare-views')
            ->not->toContain('<button');
    });

    it('omits label and caption elements when they are empty', function () {
        $html = renderBlogContent([normalizedComparison(comparisonConfig(['first_label' => '', 'second_label' => '', 'caption' => '']))]);

        expect($html)->not->toContain('figcaption');
    });

    it('uses a native, labelled range input for the optional slider and keeps both alts', function () {
        $node = normalizedComparison(comparisonConfig());
        $html = squish(renderBlogComponent('ImageComparisonSlider', ['first' => $node['first'], 'second' => $node['second']]));

        expect(preg_match_all('/<input\b[^>]*>/', $html, $inputs))->toBe(1);
        preg_match_all('/([a-z-]+)="([^"]*)"/', $inputs[0][0], $pairs);
        $input = array_combine($pairs[1], $pairs[2]);
        preg_match('/<label for="([^"]+)" class="ni-compare-label">([^<]+)<\/label>/', $html, $label);

        expect($input)->toMatchArray([
            'type' => 'range',
            'min' => '0',
            'max' => '100',
            'value' => '50',
            'aria-valuetext' => '50% Cataract, 50% Normal vision',
        ])
            ->and($label[1])->toBe($input['id'])
            ->and(trim($label[2]))->toBe('Reveal comparison: 0% shows Normal vision, 100% shows Cataract')
            ->and($html)
            ->toContain('alt="An eye chart, sharp and clear"')
            ->toContain('alt="The same eye chart, blurred and yellowed"')
            ->toContain('<span class="ni-compare-tag" data-side="second" aria-hidden="true">Cataract</span>');
    });

    it('names the slider positions even when the images have no labels', function () {
        $node = normalizedComparison(comparisonConfig(['first_label' => null, 'second_label' => null]));
        $html = squish(renderBlogComponent('ImageComparisonSlider', ['first' => $node['first'], 'second' => $node['second']]));

        expect($html)
            ->toContain('Reveal comparison: 0% shows first image, 100% shows second image')
            ->toContain('aria-valuetext="50% second image, 50% first image"')
            ->not->toContain('ni-compare-tag');
    });
});

describe('filament editor', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->create(['email' => 'admin@novinicium.be']));
        Filament::setCurrentPanel('admin');
        Storage::fake('public');
        Storage::disk('public')->put('images/cover.jpg', 'fake');
        $this->post = Post::factory()->create(['image_path' => 'images/cover.jpg']);
    });

    /**
     * @param  array<string, mixed>  $data
     */
    function callComparisonAction(Post $post, array $data): mixed
    {
        return Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->callAction(
                TestAction::make('customBlock')
                    ->schemaComponent('body_json')
                    ->arguments(['id' => 'imageComparison', 'mode' => 'insert', 'editorSelection' => ['type' => 'text', 'anchor' => 1, 'head' => 1]]),
                data: $data,
            );
    }

    it('requires both alt texts in the form', function () {
        callComparisonAction($this->post, [
            'first_path' => UploadedFile::fake()->image('a.png'),
            'first_alt' => '',
            'second_path' => UploadedFile::fake()->image('b.png'),
            'second_alt' => '',
        ])->assertHasActionErrors(['first_alt' => 'required', 'second_alt' => 'required']);
    });

    it('rejects svg and oversized uploads like the image block', function (UploadedFile $file) {
        callComparisonAction($this->post, [
            'first_path' => $file,
            'first_alt' => 'A',
            'second_path' => UploadedFile::fake()->image('b.png'),
            'second_alt' => 'B',
        ])->assertHasActionErrors(['first_path']);
    })->with([
        'svg' => fn () => UploadedFile::fake()->createWithContent('vector.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
        'too large' => fn () => UploadedFile::fake()->image('huge.jpg')->size(ImageBlock::MAX_UPLOAD_KILOBYTES + 1),
    ]);

    it('stores both uploads under images/ and inserts a config the normalizer accepts', function () {
        callComparisonAction($this->post, [
            'first_path' => UploadedFile::fake()->image('a.png'),
            'first_alt' => 'First',
            'first_label' => 'Before',
            'second_path' => UploadedFile::fake()->image('b.jpg'),
            'second_alt' => 'Second',
        ])
            ->assertHasNoActionErrors()
            ->assertDispatched('run-rich-editor-commands', function (string $event, array $params): bool {
                $node = ImageComparisonBlock::normalize($params['commands'][0]['arguments'][0]['attrs']['config']);

                return $node !== null
                    && $node['first']['label'] === 'Before'
                    && $node['second']['label'] === null
                    && $node['second']['alt'] === 'Second';
            });

        $stored = collect(Storage::disk('public')->files('images'))->reject(fn (string $path): bool => $path === 'images/cover.jpg');

        expect($stored)->toHaveCount(2)
            ->each->toMatch(ImageBlock::PATH_PATTERN);
    });

    it('round-trips the demo comparison: seed, reopen, edit, save, render', function () {
        $this->seed(DemoArticleSeeder::class);
        $post = Post::where('slug', DemoArticleSeeder::SLUG)->sole();
        $post->update(['image_path' => 'images/cover.jpg']);

        $component = Livewire::test(EditPost::class, ['record' => $post->getRouteKey()]);
        $document = $component->get('data.body_json');
        $index = collect($document['content'])->search(fn (array $node): bool => ($node['attrs']['id'] ?? null) === 'imageComparison');

        expect($document['content'][$index]['attrs']['config']['first_label'])->toBe('Normal vision')
            ->and($document['content'][$index]['attrs']['config']['second_label'])->toBe('Simulated cataract');

        $document['content'][$index]['attrs']['config']['second_label'] = 'Cataract (simulated)';

        $component
            ->fillForm(['body_json' => $document, 'published_at' => now()->subMinute()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get(route('blog.show', $post->refresh()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('post.content', function ($content): bool {
                $comparison = collect($content)->firstWhere('type', 'imageComparison');

                return $comparison['first']['src'] === Post::storageUrlFor(DemoArticleSeeder::VISION_NORMAL_IMAGE)
                    && $comparison['second']['label'] === 'Cataract (simulated)'
                    && str_starts_with($comparison['second']['alt'], 'The same eye chart blurred');
            }));

        Storage::disk('public')->assertExists([DemoArticleSeeder::VISION_NORMAL_IMAGE, DemoArticleSeeder::VISION_CATARACT_IMAGE]);
    });
});
