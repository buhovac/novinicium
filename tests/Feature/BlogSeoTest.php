<?php

use App\Models\Post;
use App\Models\Project;
use Inertia\Testing\AssertableInertia as Assert;

// Production runs without an SSR server: assert what crawlers get there,
// independent of a local Vite dev server (which would SSR the page).
beforeEach(fn () => config(['inertia.ssr.enabled' => false]));

/**
 * The JSON-LD object in a response, decoded.
 *
 * @return array<string, mixed>|null
 */
function articleJsonLd(string $html): ?array
{
    if (preg_match('/<script type="application\/ld\+json" data-ni-article-seo>(.*?)<\/script>/s', $html, $match) !== 1) {
        return null;
    }

    return json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);
}

function publishedPost(array $attributes = []): Post
{
    return Post::factory()->create([
        'title' => 'Designing for low vision',
        'slug' => 'designing-for-low-vision',
        'excerpt' => 'How to design pages that stay readable with magnification, large text and high contrast.',
        'author' => 'Nov Inicium',
        'image_path' => 'images/cover.jpg',
        'published_at' => '2026-03-01 09:00:00',
        ...$attributes,
    ]);
}

it('puts the article metadata in the server html of a published post', function () {
    $post = publishedPost();
    $html = $this->get(route('blog.show', $post))->assertOk()->getContent();
    $url = route('blog.show', $post);

    expect($html)
        ->toContain('<meta name="description" content="How to design pages that stay readable with magnification, large text and high contrast." data-ni-article-seo>')
        ->toContain('<link rel="canonical" href="'.$url.'" data-ni-article-seo>')
        ->toContain('<meta property="og:type" content="article" data-ni-article-seo>')
        ->toContain('<meta property="og:title" content="Designing for low vision" data-ni-article-seo>')
        ->toContain('<meta property="og:url" content="'.$url.'" data-ni-article-seo>')
        ->toContain('<meta property="og:image" content="'.url('/storage/images/cover.jpg').'" data-ni-article-seo>')
        ->toContain('<meta property="og:image:alt" content="Cover illustration for the article “Designing for low vision”" data-ni-article-seo>')
        ->toContain('<meta name="twitter:card" content="summary_large_image" data-ni-article-seo>')
        ->toContain('<meta property="article:published_time" content="'.$post->published_at->toIso8601String().'" data-ni-article-seo>')
        ->toContain('<meta property="article:modified_time" content="'.$post->updated_at->toIso8601String().'" data-ni-article-seo>')
        ->and(substr_count($html, 'rel="canonical"'))->toBe(1)
        ->and(substr_count($html, 'name="description"'))->toBe(1)
        ->and($html)->not->toContain('name="robots"');
});

it('describes the article as minimal BlogPosting structured data', function () {
    $post = publishedPost();

    expect(articleJsonLd($this->get(route('blog.show', $post))->getContent()))->toBe([
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => 'Designing for low vision',
        'description' => 'How to design pages that stay readable with magnification, large text and high contrast.',
        'image' => url('/storage/images/cover.jpg'),
        'datePublished' => $post->published_at->toIso8601String(),
        'dateModified' => $post->updated_at->toIso8601String(),
        'author' => ['@type' => 'Person', 'name' => 'Nov Inicium'],
        'publisher' => ['@type' => 'Organization', 'name' => 'Example Studio'],
        'mainEntityOfPage' => route('blog.show', $post),
    ]);
});

it('marks the author as the organization when it is the site itself', function () {
    $post = publishedPost(['author' => 'Example Studio']);

    expect(articleJsonLd($this->get(route('blog.show', $post))->getContent())['author'])
        ->toBe(['@type' => 'Organization', 'name' => 'Example Studio']);
});

it('renders the article title server-side for crawlers without ssr', function () {
    $html = $this->get(route('blog.show', publishedPost()))->getContent();

    expect($html)->toContain('<title>Designing for low vision — Nov Inicium Blog - '.config('app.name').'</title>');
});

it('shortens a long excerpt at a word for the description', function () {
    $post = publishedPost(['excerpt' => str_repeat('accessible ', 40)]);
    preg_match('/<meta name="description" content="([^"]*)"/', $this->get(route('blog.show', $post))->getContent(), $match);

    expect(mb_strlen($match[1]))->toBeLessThanOrEqual(163)
        ->and($match[1])->toEndWith('accessible...');
});

it('offers no og:image for an svg cover, which social scrapers cannot show', function () {
    $html = $this->get(route('blog.show', publishedPost(['image_path' => '/images/placeholder.svg'])))->getContent();

    expect($html)
        ->not->toContain('og:image')
        ->toContain('<meta name="twitter:card" content="summary" data-ni-article-seo>')
        ->and(articleJsonLd($html))->not->toHaveKey('image');
});

it('keeps hostile text inert in meta tags and structured data', function () {
    $post = publishedPost(['title' => '</script><script>alert(1)</script>"', 'excerpt' => '"><img src=x onerror=alert(1)>']);
    $html = $this->get(route('blog.show', $post))->getContent();

    // Nothing can close a tag early: JSON-LD uses \u003C escapes, meta
    // attributes use HTML entities (Inertia's page JSON escapes "</" itself).
    expect($html)
        ->not->toContain('</script><script>alert(1)')
        ->not->toContain('"><img src=x')
        ->toContain('<meta property="og:title" content="&lt;/script&gt;&lt;script&gt;alert(1)&lt;/script&gt;&quot;" data-ni-article-seo>')
        ->toContain('<meta name="description" content="&quot;&gt;&lt;img src=x onerror=alert(1)&gt;" data-ni-article-seo>')
        ->and(articleJsonLd($html)['headline'])->toBe('</script><script>alert(1)</script>"');
});

it('exposes published and updated dates to the page', function () {
    $post = publishedPost();
    $post->forceFill(['updated_at' => '2026-04-10 12:00:00'])->saveQuietly();

    $this->get(route('blog.show', $post))->assertInertia(fn (Assert $page) => $page
        ->where('post.published_at', '2026-03-01')
        ->where('post.updated_at', '2026-04-10')
        ->where('post.updated_human', '10 April 2026')
        ->has('post.toc')
        ->has('post.reading_minutes')
        ->where('preview', null));
});

it('does not show an updated date for edits on the publishing day', function () {
    $post = publishedPost();
    $post->forceFill(['updated_at' => '2026-03-01 18:00:00'])->saveQuietly();

    $this->get(route('blog.show', $post))->assertInertia(fn (Assert $page) => $page->where('post.updated_at', null));
});

it('emits no article metadata for drafts, scheduled posts or other pages', function () {
    $draft = Post::factory()->draft()->create();
    $scheduled = Post::factory()->scheduled()->create();

    expect($this->get(route('blog.show', $draft))->assertNotFound()->getContent())->not->toContain('data-ni-article-seo')
        ->and($this->get(route('blog.show', $scheduled))->assertNotFound()->getContent())->not->toContain('data-ni-article-seo');

    foreach ([route('home'), route('blog.index'), route('portfolio.index')] as $url) {
        expect($this->get($url)->assertOk()->getContent())
            ->not->toContain('data-ni-article-seo')
            ->not->toContain('rel="canonical"')
            ->toContain('<title>'.config('app.name').'</title>');
    }
});

it('leaves the portfolio project page metadata unchanged', function () {
    $project = Project::create(['title' => 'Accessible portal', 'slug' => 'accessible-portal', 'description' => 'A project.', 'body' => 'Body.', 'sort_order' => 1]);

    expect($this->get(route('portfolio.show', $project))->assertOk()->getContent())
        ->not->toContain('data-ni-article-seo')
        ->not->toContain('rel="canonical"')
        ->toContain('<title>'.config('app.name').'</title>');
});
