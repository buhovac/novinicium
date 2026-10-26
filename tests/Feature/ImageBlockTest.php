<?php

use App\Actions\Blog\BuildPostContent;
use App\Filament\Resources\Posts\Blocks\ImageBlock;
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
function imageConfig(array $overrides = []): array
{
    return [
        'path' => 'images/photo.jpg',
        'decorative' => false,
        'alt' => 'A white cane resting against a park bench',
        'caption' => 'White canes help people navigate independently.',
        'credit' => 'Photo: Nov Inicium',
        'width' => 'wide',
        ...$overrides,
    ];
}

/**
 * @param  array<string, mixed>|string|null  $config
 * @return array<string, mixed>|null
 */
function normalizedImage(array|string|null $config): ?array
{
    $post = Post::factory()->make(['body_json' => ['type' => 'doc', 'content' => [customBlockNode('image', $config)]]]);

    return app(BuildPostContent::class)->handle($post)[0] ?? null;
}

it('renders a valid image as a figure node with alt, caption and credit kept separate', function () {
    $post = postWithBody([customBlockNode('image', imageConfig())]);

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('post.content', [[
            'type' => 'image',
            'src' => '/storage/images/photo.jpg',
            'alt' => 'A white cane resting against a park bench',
            'decorative' => false,
            'caption' => 'White canes help people navigate independently.',
            'credit' => 'Photo: Nov Inicium',
            'width' => 'wide',
        ]]));
});

it('resolves the stored path through Post::storageUrlFor()', function () {
    expect(normalizedImage(imageConfig(['path' => 'images/a-b_c.1.webp']))['src'])
        ->toBe(Post::storageUrlFor('images/a-b_c.1.webp'))
        ->toBe('/storage/images/a-b_c.1.webp');
});

it('rejects a non-decorative image without alt text', function (mixed $alt) {
    expect(normalizedImage(imageConfig(['alt' => $alt])))->toBeNull();
})->with(['missing' => null, 'empty' => '', 'whitespace' => '   ', 'not a string' => [['x']]]);

it('requires decorative to be a real boolean before alt can be empty', function (mixed $decorative) {
    expect(normalizedImage(imageConfig(['decorative' => $decorative, 'alt' => ''])))->toBeNull();
})->with(['string true' => 'true', 'string 1' => '1', 'int 1' => 1]);

it('allows an empty alt for decorative images and drops their caption and credit', function () {
    expect(normalizedImage(imageConfig(['decorative' => true, 'alt' => 'ignored'])))->toBe([
        'type' => 'image',
        'src' => '/storage/images/photo.jpg',
        'alt' => '',
        'decorative' => true,
        'caption' => null,
        'credit' => null,
        'width' => 'wide',
    ]);
});

it('keeps caption and alt as separate fields', function () {
    $node = normalizedImage(imageConfig(['caption' => null, 'credit' => '']));

    expect($node['alt'])->toBe('A white cane resting against a park bench')
        ->and($node['caption'])->toBeNull()
        ->and($node['credit'])->toBeNull();
});

it('only accepts uploaded raster images from the images folder', function (string $path) {
    expect(normalizedImage(imageConfig(['path' => $path])))->toBeNull();
})->with([
    'external url' => 'https://evil.example/x.png',
    'protocol relative' => '//evil.example/x.png',
    'javascript' => 'javascript:alert(1)',
    'path traversal' => 'images/../.env',
    'other folder' => 'livewire-tmp/x.png',
    'svg' => 'images/x.svg',
    'no extension' => 'images/x',
]);

it('falls back to standard width for an unknown width', function () {
    expect(normalizedImage(imageConfig(['width' => 'huge']))['width'])->toBe('standard');
});

it('drops malformed image blocks without breaking the article', function (array|string|null $config) {
    $post = postWithBody([paragraphNode('Before'), customBlockNode('image', $config), paragraphNode('After')]);

    expect(app(BuildPostContent::class)->handle($post))->toHaveCount(2);
})->with(['null' => [null], 'string' => ['x'], 'empty' => [[]], 'missing path' => [['alt' => 'x', 'decorative' => false]]]);

it('writes a figure into the html snapshot', function () {
    $post = postWithBody([customBlockNode('image', imageConfig())]);

    expect($post->body)
        ->toContain('<figure>')
        ->toContain('src="/storage/images/photo.jpg"')
        ->toContain('alt="A white cane resting against a park bench"')
        ->toContain('<figcaption>');
});

it('shows a preview card in the editor', function () {
    expect(ImageBlock::getPreviewLabel(imageConfig()))->toBe('Image — A white cane resting against a park bench')
        ->and(ImageBlock::getPreviewLabel(imageConfig(['decorative' => true])))->toBe('Image — decorative')
        ->and(ImageBlock::getPreviewLabel(imageConfig(['alt' => ''])))->toBe('Image (incomplete)')
        ->and(ImageBlock::toPreviewHtml(imageConfig(['alt' => '<b>x</b>'])))->toContain('&lt;b&gt;x&lt;/b&gt;');
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
    function callImageAction(Post $post, array $data): mixed
    {
        return Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->callAction(
                TestAction::make('customBlock')
                    ->schemaComponent('body_json')
                    ->arguments(['id' => 'image', 'mode' => 'insert', 'editorSelection' => ['type' => 'text', 'anchor' => 1, 'head' => 1]]),
                data: $data,
            );
    }

    it('requires alt text in the form for non-decorative images', function () {
        callImageAction($this->post, [
            'path' => UploadedFile::fake()->image('photo.jpg'),
            'decorative' => false,
            'alt' => '',
            'width' => 'standard',
        ])->assertHasActionErrors(['alt' => 'required']);
    });

    it('accepts a decorative image without alt text', function () {
        callImageAction($this->post, [
            'path' => UploadedFile::fake()->image('divider.png'),
            'decorative' => true,
            'width' => 'full',
        ])->assertHasNoActionErrors();
    });

    it('rejects invalid file types and oversized uploads', function (UploadedFile $file) {
        callImageAction($this->post, [
            'path' => $file,
            'decorative' => false,
            'alt' => 'Alt',
            'width' => 'standard',
        ])->assertHasActionErrors(['path']);
    })->with([
        'pdf' => fn () => UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'),
        'svg' => fn () => UploadedFile::fake()->createWithContent('vector.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>'),
        'too large' => fn () => UploadedFile::fake()->image('huge.jpg')->size(ImageBlock::MAX_UPLOAD_KILOBYTES + 1),
    ]);

    it('stores a valid upload on the public disk under images/', function () {
        callImageAction($this->post, [
            'path' => UploadedFile::fake()->image('photo.jpg'),
            'decorative' => false,
            'alt' => 'A photo',
            'width' => 'standard',
        ])
            ->assertHasNoActionErrors()
            // The block inserted into the editor carries the stored path as a
            // plain string, which the normalizer accepts.
            ->assertDispatched('run-rich-editor-commands', function (string $event, array $params): bool {
                $config = $params['commands'][0]['arguments'][0]['attrs']['config'];

                return is_string($config['path'])
                    && Storage::disk('public')->exists($config['path'])
                    && ImageBlock::normalize($config)['src'] === Post::storageUrlFor($config['path']);
            });

        $stored = collect(Storage::disk('public')->files('images'))->reject(fn (string $path): bool => $path === 'images/cover.jpg');

        expect($stored)->toHaveCount(1)
            ->and($stored->first())->toMatch(ImageBlock::PATH_PATTERN);
    });

    it('round-trips the demo image: seed, reopen, edit alt, save, render', function () {
        $this->seed(DemoArticleSeeder::class);
        $post = Post::where('slug', DemoArticleSeeder::SLUG)->sole();
        $post->update(['image_path' => 'images/cover.jpg']);

        $component = Livewire::test(EditPost::class, ['record' => $post->getRouteKey()]);
        $document = $component->get('data.body_json');
        $index = collect($document['content'])->search(fn (array $node): bool => ($node['attrs']['config']['path'] ?? null) === DemoArticleSeeder::CONTRAST_IMAGE);

        expect($document['content'][$index]['attrs']['config']['credit'])->toBe('Illustration: Nov Inicium, generated for this demo article.');

        $document['content'][$index]['attrs']['config']['alt'] = 'Edited alt text';

        $component
            ->fillForm(['body_json' => $document, 'published_at' => now()->subMinute()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get(route('blog.show', $post->refresh()))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('post.content', function ($content): bool {
                $images = collect($content)->where('type', 'image');

                return $images->count() === 2
                    && $images->firstWhere('decorative', false)['alt'] === 'Edited alt text'
                    && $images->firstWhere('decorative', true)['alt'] === '';
            }));

        Storage::disk('public')->assertExists([DemoArticleSeeder::CONTRAST_IMAGE, DemoArticleSeeder::DIVIDER_IMAGE]);
    });
});
