<?php

use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\Post;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;

beforeEach(fn () => config(['inertia.ssr.enabled' => false]));

function admin(): User
{
    return User::factory()->create(['email' => 'editor@novinicium.be']);
}

it('keeps drafts and scheduled posts off the public route', function (string $state) {
    $post = Post::factory()->{$state}()->create();

    $this->get(route('blog.show', $post))->assertNotFound();
    $this->actingAs(admin())->get(route('blog.show', $post))->assertNotFound();
})->with(['draft', 'scheduled']);

it('sends anonymous visitors to log in instead of showing the preview', function (string $state) {
    $post = Post::factory()->{$state}()->create(['title' => 'Secret draft title']);

    $response = $this->get(route('blog.preview', $post));

    $response->assertRedirect(route('login'));
    expect($response->getContent())->not->toContain('Secret draft title');
})->with(['draft', 'scheduled']);

it('forbids signed-in users who cannot access the admin panel', function () {
    $post = Post::factory()->draft()->create(['title' => 'Secret draft title']);

    $response = $this->actingAs(User::factory()->create(['email' => 'reader@example.com']))
        ->get(route('blog.preview', $post));

    $response->assertForbidden();
    expect($response->getContent())->not->toContain('Secret draft title');
});

it('lets an admin preview a draft through the public article page', function () {
    $post = Post::factory()->draft()->create([
        'title' => 'Draft about magnifiers',
        'body_json' => ['type' => 'doc', 'content' => [
            ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Magnifiers']]],
            paragraphNode('Draft body.'),
        ]],
    ]);

    $response = $this->actingAs(admin())->get(route('blog.preview', $post));

    $response->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Blog/Show')
            ->where('post.title', 'Draft about magnifiers')
            ->where('post.content.0.id', 'magnifiers')
            ->where('post.toc.0.id', 'magnifiers')
            ->where('post.published_at', null)
            ->where('preview.status', 'draft'));

    expect($response->headers->get('Cache-Control'))->toContain('no-store')->toContain('private')
        ->and($response->getContent())
        ->toContain('<meta name="robots" content="noindex, nofollow" data-ni-article-seo>')
        ->not->toContain('rel="canonical"')
        ->not->toContain('og:title')
        ->not->toContain('application/ld+json');
});

it('labels scheduled and published previews', function () {
    $scheduled = Post::factory()->create(['published_at' => '2030-01-15 09:30:00']);
    $published = Post::factory()->create();

    $this->actingAs(admin());

    $this->get(route('blog.preview', $scheduled))->assertInertia(fn (Assert $page) => $page
        ->where('preview.status', 'scheduled')
        ->where('preview.scheduled_human', '15 January 2030, 09:30'));

    $this->get(route('blog.preview', $published))->assertInertia(fn (Assert $page) => $page
        ->where('preview.status', 'published'));
});

it('does not publish or change the post when previewing', function () {
    $post = Post::factory()->draft()->create();
    $before = $post->fresh()->toArray();

    $this->actingAs(admin())->get(route('blog.preview', $post))->assertOk();

    expect($post->fresh()->toArray())->toBe($before)
        ->and($post->fresh()->published_at)->toBeNull();
});

it('renders the preview with the same props as the public page', function () {
    $post = Post::factory()->create();
    $this->actingAs(admin());

    $public = $this->get(route('blog.show', $post))->viewData('page')['props']['post'];
    $preview = $this->get(route('blog.preview', $post))->viewData('page')['props']['post'];

    expect($preview)->toBe($public);
});

it('offers a preview button in the Filament editor', function () {
    $this->actingAs(admin());
    Filament::setCurrentPanel('admin');
    Storage::fake('public');
    $post = Post::factory()->draft()->create(['image_path' => 'images/cover.jpg']);

    Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
        ->assertActionExists('preview')
        ->assertActionHasUrl('preview', route('blog.preview', $post))
        ->assertActionShouldOpenUrlInNewTab('preview');
});
