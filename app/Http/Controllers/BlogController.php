<?php

namespace App\Http\Controllers;

use App\Actions\Blog\ArticleOutline;
use App\Actions\Blog\BuildPostContent;
use App\Models\Post;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BlogController extends Controller
{
    public const META_DESCRIPTION_MAX_LENGTH = 160;

    public function __construct(
        private BuildPostContent $buildPostContent,
        private ArticleOutline $outline,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Blog/Index', [
            'posts' => Post::published()
                ->latest('published_at')
                ->paginate(9)
                ->through(fn (Post $post) => [
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'excerpt' => $post->excerpt,
                    'image_path' => $post->image_url,
                    'author' => $post->author,
                    'published_at' => $post->published_at->toDateString(),
                    'published_human' => $post->published_at->isoFormat('D MMMM YYYY'),
                ]),
        ]);
    }

    public function show(Post $post): Response
    {
        // Drafts and scheduled posts are not public even if the slug is guessed.
        abort_unless($this->isPublic($post), 404);

        return Inertia::render('Blog/Show', [
            'post' => $this->articleProps($post),
            'preview' => null,
        ])->withViewData(['seo' => $this->seo($post)]);
    }

    /**
     * Admin-only preview (route: auth + can:preview-posts) of any post,
     * including drafts and scheduled posts, through the same page and
     * renderer as the public article. Never indexed or cached, and no
     * SEO/social metadata is emitted.
     */
    public function preview(Request $request, Post $post): HttpResponse
    {
        $status = match (true) {
            $post->published_at === null => 'draft',
            ! $this->isPublic($post) => 'scheduled',
            default => 'published',
        };

        $response = Inertia::render('Blog/Show', [
            'post' => $this->articleProps($post),
            'preview' => [
                'status' => $status,
                'scheduled_human' => $status === 'scheduled' ? $post->published_at->isoFormat('D MMMM YYYY, HH:mm') : null,
            ],
        ])
            ->withViewData(['robots' => 'noindex, nofollow'])
            ->toResponse($request);

        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    private function isPublic(Post $post): bool
    {
        return $post->published_at !== null && $post->published_at->isPast();
    }

    /**
     * @return array<string, mixed>
     */
    private function articleProps(Post $post): array
    {
        $content = $this->buildPostContent->handle($post);
        $isUpdatedLater = $post->published_at !== null
            && $post->updated_at !== null
            && $post->updated_at->startOfDay()->greaterThan($post->published_at->startOfDay());

        return [
            'title' => $post->title,
            'content' => $content,
            'toc' => $this->outline->tableOfContents($content),
            'reading_minutes' => $this->outline->readingMinutes($content),
            'image_path' => $post->image_url,
            'author' => $post->author,
            'published_at' => $post->published_at?->toDateString(),
            'published_human' => $post->published_at?->isoFormat('D MMMM YYYY'),
            'updated_at' => $isUpdatedLater ? $post->updated_at->toDateString() : null,
            'updated_human' => $isUpdatedLater ? $post->updated_at->isoFormat('D MMMM YYYY') : null,
        ];
    }

    /**
     * Server-rendered metadata for crawlers and link previews (rendered by
     * resources/views/app.blade.php, so it is in the HTML without SSR).
     * The description is the post's required excerpt, shortened at a word.
     *
     * @return array<string, mixed>
     */
    private function seo(Post $post): array
    {
        $siteName = (string) (SiteSetting::shared()['footer_company_name'] ?? config('app.name'));
        $description = Str::limit(trim((string) preg_replace('/\s+/', ' ', (string) $post->excerpt)), self::META_DESCRIPTION_MAX_LENGTH, preserveWords: true);
        $canonical = route('blog.show', $post);

        // Social scrapers do not render SVG, so only raster covers are offered.
        $image = $post->image_url !== null && preg_match('/\.(jpe?g|png|webp|gif)$/i', $post->image_url) === 1
            ? url($post->image_url)
            : null;
        $imageAlt = "Cover illustration for the article “{$post->title}”";

        return [
            'title' => $post->title,
            'description' => $description,
            'canonical' => $canonical,
            'site_name' => $siteName,
            'image' => $image,
            'image_alt' => $imageAlt,
            'published_time' => $post->published_at?->toIso8601String(),
            'modified_time' => $post->updated_at?->toIso8601String(),
            'json_ld' => array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'BlogPosting',
                'headline' => Str::limit($post->title, 110, preserveWords: true),
                'description' => $description,
                'image' => $image,
                'datePublished' => $post->published_at?->toIso8601String(),
                'dateModified' => $post->updated_at?->toIso8601String(),
                'author' => [
                    '@type' => $post->author === $siteName ? 'Organization' : 'Person',
                    'name' => $post->author,
                ],
                'publisher' => ['@type' => 'Organization', 'name' => $siteName],
                'mainEntityOfPage' => $canonical,
            ], fn (mixed $value): bool => $value !== null),
        ];
    }
}
