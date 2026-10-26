<?php

use App\Actions\Blog\BuildPostContent;
use App\Filament\Resources\Posts\Blocks\CodeBlock;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\DemoArticleSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

/**
 * @param  array<string, mixed>|string|null  $config
 * @return array<string, mixed>|null
 */
function normalizedCode(array|string|null $config): ?array
{
    $post = Post::factory()->make(['body_json' => ['type' => 'doc', 'content' => [customBlockNode('code', $config)]]]);

    return app(BuildPostContent::class)->handle($post)[0] ?? null;
}

it('renders code with its language label', function () {
    $post = postWithBody([customBlockNode('code', ['language' => 'php', 'code' => "<?php\n\necho 'hi';"])]);

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('post.content', [[
            'type' => 'code',
            'language' => 'php',
            'label' => 'PHP',
            'code' => "<?php\n\necho 'hi';",
        ]]));
});

it('keeps html and scripts in code as plain text', function () {
    $code = '<script>alert(1)</script><img src=x onerror=alert(2)>';
    $post = postWithBody([customBlockNode('code', ['language' => 'html', 'code' => $code])]);

    // A plain string for {{ }} interpolation: shown as text, never parsed.
    expect(app(BuildPostContent::class)->handle($post)[0]['code'])->toBe($code);

    expect($post->body)
        ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
        ->not->toContain('<script>')
        ->not->toContain('<img');
});

it('preserves indentation and trims only surrounding blank lines', function () {
    expect(normalizedCode(['language' => 'php', 'code' => "\n\n    if (true) {\n        go();\n    }\n\n  \n"])['code'])
        ->toBe("    if (true) {\n        go();\n    }");
});

it('maps unknown or loose language names to a known label', function (mixed $language, string $label) {
    expect(normalizedCode(['language' => $language, 'code' => 'x'])['label'])->toBe($label);
})->with([
    'alias js' => ['js', 'JavaScript'],
    'alias sh' => ['sh', 'Shell'],
    'unknown' => ['brainfuck', 'Plain text'],
    'injection attempt' => ['php"><script>', 'Plain text'],
    'missing' => [null, 'Plain text'],
]);

it('drops empty or malformed code blocks without breaking the article', function (array|string|null $config) {
    $post = postWithBody([paragraphNode('Before'), customBlockNode('code', $config), paragraphNode('After')]);

    expect(app(BuildPostContent::class)->handle($post))->toHaveCount(2);
})->with([
    'null' => [null],
    'empty' => [[]],
    'blank code' => [['language' => 'php', 'code' => "  \n \n"]],
    'code not a string' => [['language' => 'php', 'code' => ['x']]],
    'too long' => [['language' => 'php', 'code' => str_repeat('a', CodeBlock::CODE_MAX_LENGTH + 1)]],
]);

it('renders native code blocks through the same code node', function () {
    $post = Post::factory()->make(['body' => '<pre><code class="language-js">let a = 1;</code></pre>']);

    expect(app(BuildPostContent::class)->handle($post))->toBe([[
        'type' => 'code',
        'language' => 'javascript',
        'label' => 'JavaScript',
        'code' => 'let a = 1;',
    ]]);
});

it('shows a preview card with the language and the first lines', function () {
    $html = CodeBlock::toPreviewHtml(['language' => 'php', 'code' => "a\nb\nc\nd\ne\n<b>"]);

    expect(CodeBlock::getPreviewLabel(['language' => 'php', 'code' => 'x']))->toBe('Code — PHP')
        ->and($html)->toContain('<strong>PHP</strong> · 6 lines')
        ->toContain("a\nb\nc\nd\n…")
        ->not->toContain('<b>');
});

it('has a labelled, keyboard-operable copy button in the Vue component', function () {
    // Whitespace-insensitive: Vue condenses it, so formatting must not matter.
    $component = (string) preg_replace('/\s+/', ' ', (string) file_get_contents(resource_path('js/components/blog/ArticleCode.vue')));

    // A native <button> is focusable and handles Enter/Space by default; its
    // name includes the visible text ("Copy") plus the language for context.
    expect($component)
        ->toContain('<button type="button" class="ni-code-copy" @click="copyCode">')
        ->toContain('>Copy<span class="sr-only"> {{ node.label }} code</span')
        ->toContain('aria-live="polite"')
        ->not->toContain('v-html');
});

it('round-trips the demo code block: seed, reopen, edit, save, render', function () {
    $this->actingAs(User::factory()->create(['email' => 'admin@novinicium.be']));
    Filament::setCurrentPanel('admin');
    Storage::fake('public');
    Storage::disk('public')->put('images/cover.jpg', 'fake');

    $this->seed(DemoArticleSeeder::class);
    $post = Post::where('slug', DemoArticleSeeder::SLUG)->sole();
    $post->update(['image_path' => 'images/cover.jpg']);

    $component = Livewire::test(EditPost::class, ['record' => $post->getRouteKey()]);
    $document = $component->get('data.body_json');
    $index = collect($document['content'])->search(fn (array $node): bool => ($node['attrs']['id'] ?? null) === 'code');

    expect($document['content'][$index]['attrs']['config']['language'])->toBe('html');

    $document['content'][$index]['attrs']['config']['code'] = '<p>Edited</p>';

    $component
        ->fillForm(['body_json' => $document, 'published_at' => now()->subMinute()])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->get(route('blog.show', $post->refresh()))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where(
            'post.content',
            fn ($content): bool => collect($content)->firstWhere('type', 'code')['code'] === '<p>Edited</p>',
        ));
});
