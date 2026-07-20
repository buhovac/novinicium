<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Project;
use Illuminate\Database\Seeder;

/**
 * Demo content only. Replace with your own posts and projects,
 * or manage content through the Filament admin panel.
 */
class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        // ---------- Blog posts ----------
        $posts = [
            [
                'title' => 'Hello World: First Post on This Site',
                'slug' => 'hello-world',
                'excerpt' => 'A short example post demonstrating how blog content is structured: title, excerpt, body, cover image and publication date.',
                'body' => "This is placeholder content for the first example post.\n\nEach post has a title, a URL slug, a short excerpt shown in listings, a full body, a cover image and a publication date. Paragraphs in the body are separated by blank lines.\n\nReplace this text with your own writing, or delete the demo posts entirely and create new ones through the admin panel.",
                'image_path' => '/images/placeholder.svg',
                'author' => 'Jane Doe',
                'published_at' => now()->subDays(14),
            ],
            [
                'title' => 'A Second Example Post',
                'slug' => 'second-example-post',
                'excerpt' => 'Another placeholder entry so the blog listing shows more than one item during development.',
                'body' => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.\n\nUt enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.\n\nDuis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.",
                'image_path' => '/images/placeholder.svg',
                'author' => 'Jane Doe',
                'published_at' => now()->subDays(3),
            ],
        ];

        foreach ($posts as $post) {
            Post::updateOrCreate(['slug' => $post['slug']], $post);
        }

        // ---------- Portfolio projects ----------
        $alpha = Project::updateOrCreate(
            ['slug' => 'example-project-alpha'],
            [
                'title' => 'Example Project Alpha',
                'description' => 'A placeholder portfolio entry showing how a project is presented: short description, long body and an image gallery.',
                'body' => "This is demo content for a portfolio project.\n\nEach project has a title, a slug, a card description, a detailed body and an ordered set of gallery images with alt text.\n\nReplace it with a real case study, or manage projects through the admin panel.",
                'live_url' => 'https://example.com',
                'sort_order' => 1,
            ],
        );
        $alpha->images()->delete();
        $alpha->images()->createMany([
            ['image_path' => '/images/placeholder.svg', 'alt_text' => 'Placeholder screenshot for the first gallery image of Example Project Alpha', 'order' => 1],
            ['image_path' => '/images/placeholder.svg', 'alt_text' => 'Placeholder screenshot for the second gallery image of Example Project Alpha', 'order' => 2],
        ]);

        $beta = Project::updateOrCreate(
            ['slug' => 'example-project-beta'],
            [
                'title' => 'Example Project Beta',
                'description' => 'A second placeholder project so the portfolio grid shows multiple items during development.',
                'body' => "Lorem ipsum dolor sit amet, consectetur adipiscing elit.\n\nSed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.",
                'live_url' => null,
                'sort_order' => 2,
            ],
        );
        $beta->images()->delete();
        $beta->images()->createMany([
            ['image_path' => '/images/placeholder.svg', 'alt_text' => 'Placeholder screenshot for Example Project Beta', 'order' => 1],
        ]);
    }
}
