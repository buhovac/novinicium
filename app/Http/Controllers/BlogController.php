<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
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
        // Drafts are not public even if the slug is guessed.
        abort_unless(
            $post->published_at !== null && $post->published_at->isPast(),
            404
        );

        return Inertia::render('Blog/Show', [
            'post' => [
                'title' => $post->title,
                'body' => $post->body,
                'image_path' => $post->image_url,
                'author' => $post->author,
                'published_at' => $post->published_at->toDateString(),
                'published_human' => $post->published_at->isoFormat('D MMMM YYYY'),
            ],
        ]);
    }
}
