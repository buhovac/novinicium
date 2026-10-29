<?php

use App\Actions\Blog\BuildPostContent;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\Post;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

/**
 * @param  array<int, array<string, mixed>>  $content
 * @return array<string, mixed>
 */
function tipTapDocument(array $content): array
{
    return ['type' => 'doc', 'content' => $content];
}

/**
 * @param  array<int, array<string, mixed>>  $marks
 * @return array<string, mixed>
 */
function text(string $text, array $marks = []): array
{
    return ['type' => 'text', 'text' => $text, 'marks' => $marks];
}

describe('public visibility', function () {
    it('hides drafts and scheduled posts', function (string $state) {
        $post = Post::factory()->{$state}()->create();

        $this->get(route('blog.show', $post))->assertNotFound();
    })->with(['draft', 'scheduled']);

    it('passes a normalized content tree instead of raw body HTML', function () {
        $post = Post::factory()->create(['body' => '<p>Hello <strong>world</strong></p>']);

        $this->get(route('blog.show', $post))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Blog/Show')
                ->missing('post.body')
                ->where('post.content', [
                    ['type' => 'paragraph', 'content' => [
                        text('Hello '),
                        text('world', [['type' => 'bold']]),
                    ]],
                ]));
    });
});

describe('xss', function () {
    it('keeps scripts, event handlers and unsafe links inert', function () {
        $post = Post::factory()->create(['body' => implode('', [
            '<script>alert("s")</script>',
            '<p onclick="alert(1)">Click<img src="x" onerror="alert(2)"></p>',
            '<p><a href="javascript:alert(3)">js</a> <a href="java&#x09;script:alert(4)">tab</a> ',
            '<a href="data:text/html,hi">data</a> <a href="//evil.example">proto</a></p>',
            '<iframe src="https://evil.example"></iframe>',
        ])]);

        $content = app(BuildPostContent::class)->handle($post);
        $json = json_encode($content);

        expect($json)
            ->not->toContain('script:')
            ->not->toContain('alert(')
            ->not->toContain('onerror')
            ->not->toContain('onclick')
            ->not->toContain('data:')
            ->not->toContain('evil.example')
            ->not->toContain('iframe');

        // Link text survives, the unsafe links themselves are dropped.
        expect($content[1]['content'][0]['text'])->toStartWith('js tab data')
            ->and($content[1]['content'][0]['marks'])->toBe([]);
    });

    it('rejects unsafe urls coming from stored json too', function () {
        $post = Post::factory()->make([
            'body_json' => tipTapDocument([
                ['type' => 'paragraph', 'content' => [
                    ['type' => 'text', 'text' => 'x', 'marks' => [['type' => 'link', 'attrs' => ['href' => ' JaVaScRiPt:alert(1)']]]],
                    ['type' => 'text', 'text' => 'ok', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'mailto:hi@example.com']]]],
                    ['type' => 'image', 'attrs' => ['src' => 'data:image/svg+xml,<svg onload=alert(1)>', 'alt' => '']],
                ]],
                ['type' => 'rawHtml', 'attrs' => ['html' => '<script>alert(1)</script>']],
            ]),
        ]);

        expect(app(BuildPostContent::class)->handle($post))->toBe([
            ['type' => 'paragraph', 'content' => [
                text('x'),
                text('ok', [['type' => 'link', 'href' => 'mailto:hi@example.com', 'newTab' => false]]),
            ]],
        ]);
    });
});

describe('legacy parity', function () {
    it('renders legacy RichEditor html through the same node tree', function () {
        $post = Post::factory()->make(['body' => implode('', [
            '<h2>Section</h2>',
            '<p><strong>Bold</strong> and <em>italic</em> with <a href="https://example.com" target="_blank">a link</a>.</p>',
            '<h3>Sub</h3>',
            '<ul><li><p>One</p></li><li><p>Two</p></li></ul>',
            '<ol><li><p>First</p></li></ol>',
            '<blockquote><p>Quote</p></blockquote>',
        ])]);

        expect(app(BuildPostContent::class)->handle($post))->toBe([
            ['type' => 'heading', 'level' => 2, 'content' => [text('Section')], 'id' => 'section'],
            ['type' => 'paragraph', 'content' => [
                text('Bold', [['type' => 'bold']]),
                text(' and '),
                text('italic', [['type' => 'italic']]),
                text(' with '),
                text('a link', [['type' => 'link', 'href' => 'https://example.com', 'newTab' => true]]),
                text('.'),
            ]],
            ['type' => 'heading', 'level' => 3, 'content' => [text('Sub')], 'id' => 'sub'],
            ['type' => 'bulletList', 'content' => [
                ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [text('One')]]]],
                ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [text('Two')]]]],
            ]],
            ['type' => 'orderedList', 'start' => 1, 'content' => [
                ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [text('First')]]]],
            ]],
            ['type' => 'blockquote', 'content' => [['type' => 'paragraph', 'content' => [text('Quote')]]]],
        ]);
    });

    it('splits legacy plain-text bodies into paragraphs and escapes them', function () {
        $post = Post::factory()->make(['body' => "First paragraph.\n\nSecond: 5 > 3 & 2 < 4.\r\n\r\nThird."]);

        expect(app(BuildPostContent::class)->handle($post))->toBe([
            ['type' => 'paragraph', 'content' => [text('First paragraph.')]],
            ['type' => 'paragraph', 'content' => [text('Second: 5 > 3 & 2 < 4.')]],
            ['type' => 'paragraph', 'content' => [text('Third.')]],
        ]);
    });

    it('wraps stray inline text left at block level in a paragraph', function () {
        $post = Post::factory()->make(['body' => '<p>a</p>loose text<p>b</p>']);

        expect(app(BuildPostContent::class)->handle($post))->toBe([
            ['type' => 'paragraph', 'content' => [text('a')]],
            ['type' => 'paragraph', 'content' => [text('loose text')]],
            ['type' => 'paragraph', 'content' => [text('b')]],
        ]);
    });
});

describe('headings', function () {
    it('demotes h1 to h2 and deeper headings to h3', function (string $body, int $level) {
        $post = Post::factory()->make(['body' => $body]);

        expect(app(BuildPostContent::class)->handle($post)[0]['level'])->toBe($level);
    })->with([
        'h1' => ['<h1>Title</h1>', 2],
        'h4' => ['<h4>Deep</h4>', 3],
        'h6' => ['<h6>Deeper</h6>', 3],
    ]);

    it('demotes h1 stored in json', function () {
        $post = Post::factory()->make(['body_json' => tipTapDocument([
            ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'T']]],
        ])]);

        expect(app(BuildPostContent::class)->handle($post))
            ->toBe([['type' => 'heading', 'level' => 2, 'content' => [text('T')], 'id' => 't']]);
    });
});

describe('native nodes', function () {
    it('normalizes code blocks, tables, grids and horizontal rules', function () {
        $post = Post::factory()->make(['body_json' => tipTapDocument([
            ['type' => 'codeBlock', 'attrs' => ['language' => 'php"><script>'], 'content' => [['type' => 'text', 'text' => '<?php echo 1;']]],
            ['type' => 'horizontalRule'],
            ['type' => 'table', 'content' => [
                ['type' => 'tableRow', 'content' => [
                    ['type' => 'tableHeader', 'attrs' => ['colspan' => 2], 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'H']]]]],
                ]],
                ['type' => 'tableRow', 'content' => [
                    ['type' => 'tableCell', 'content' => [['type' => 'text', 'text' => 'c']]],
                ]],
            ]],
            ['type' => 'grid', 'attrs' => ['data-cols' => '99', 'data-from-breakpoint' => 'evil'], 'content' => [
                ['type' => 'gridColumn', 'attrs' => ['data-col-span' => '1'], 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'col']]]]],
            ]],
            ['type' => 'details', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'dropped']]]]],
        ])]);

        expect(app(BuildPostContent::class)->handle($post))->toBe([
            ['type' => 'code', 'language' => 'plaintext', 'label' => 'Plain text', 'code' => '<?php echo 1;'],
            ['type' => 'horizontalRule'],
            ['type' => 'table', 'caption' => null, 'content' => [
                ['type' => 'tableRow', 'content' => [
                    ['type' => 'tableHeader', 'colspan' => 2, 'rowspan' => 1, 'content' => [['type' => 'paragraph', 'content' => [text('H')]]]],
                ]],
                ['type' => 'tableRow', 'content' => [
                    ['type' => 'tableCell', 'colspan' => 1, 'rowspan' => 1, 'content' => [['type' => 'paragraph', 'content' => [text('c')]]]],
                ]],
            ]],
            ['type' => 'grid', 'columns' => 12, 'fromBreakpoint' => 'lg', 'content' => [
                ['type' => 'gridColumn', 'span' => 1, 'content' => [['type' => 'paragraph', 'content' => [text('col')]]]],
            ]],
        ]);
    });
});

describe('html snapshot', function () {
    it('mirrors body_json into a sanitized body on save', function () {
        $post = Post::factory()->create(['body' => '<p>old</p>']);

        $post->update(['body_json' => tipTapDocument([
            ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'New']]],
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'x', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']]]]]],
        ])]);

        expect($post->fresh()->body)
            ->toContain('<h2')
            ->toContain('New')
            ->not->toContain('old')
            ->not->toContain('javascript:');
    });

    it('leaves legacy body untouched while body_json is empty', function () {
        $post = Post::factory()->create(['body' => '<p>legacy</p>']);

        $post->update(['title' => 'Renamed']);

        expect($post->fresh()->body)->toBe('<p>legacy</p>');
    });
});

describe('filament editor', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->create(['email' => 'admin@novinicium.be']));
        Filament::setCurrentPanel('admin');
        Storage::fake('public');
        Storage::disk('public')->put('images/cover.jpg', 'fake');
    });

    it('prefills the editor from legacy body and migrates the post on save', function () {
        $post = Post::factory()->create([
            'body' => "Plain one.\n\nPlain two.",
            'image_path' => 'images/cover.jpg',
        ]);

        Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->assertSchemaStateSet(fn (array $state): bool => count($state['body_json']['content']) === 2)
            ->call('save')
            ->assertHasNoFormErrors();

        $post->refresh();

        expect($post->body_json['content'])->toHaveCount(2)
            ->and($post->body)->toContain('<p>Plain one.</p>')->toContain('<p>Plain two.</p>');
    });

    it('creates a post with json body and an html snapshot', function () {
        Livewire::test(CreatePost::class)
            ->fillForm([
                'title' => 'Fresh post',
                'slug' => 'fresh-post',
                'excerpt' => 'Short.',
                'body_json' => tipTapDocument([
                    ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Body text']]],
                ]),
                'image_path' => ['images/cover.jpg'],
                'author' => 'Marko Buhovac',
                'published_at' => now()->subHour(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::where('slug', 'fresh-post')->sole();

        expect($post->body_json['content'][0]['type'])->toBe('paragraph')
            ->and($post->body)->toContain('Body text');
    });
});
