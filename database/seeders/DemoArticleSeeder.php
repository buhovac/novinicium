<?php

namespace Database\Seeders;

use App\Filament\Resources\Posts\Blocks\CalloutVariant;
use App\Models\Post;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Dev-only demo article that exercises every blog block ("living test
 * harness"). Each block session adds its block here.
 *
 * Run manually: php artisan db:seed --class=DemoArticleSeeder
 * It is not called from DatabaseSeeder, refuses to run in production and
 * saves the post as a draft (publish it locally in Filament to view it).
 */
class DemoArticleSeeder extends Seeder
{
    public const SLUG = 'demo-visual-impairments';

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoArticleSeeder is for local development only.');
        }

        Post::updateOrCreate(['slug' => self::SLUG], [
            'title' => 'Visual Impairments: Types, Causes and Assistive Technologies',
            'excerpt' => 'Demo article for the blog block system: an overview of visual impairments, their main causes and the assistive technologies people use online.',
            'body_json' => $this->document(),
            'image_path' => '/images/placeholder.svg',
            'author' => 'Nov Inicium',
            'published_at' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function document(): array
    {
        return ['type' => 'doc', 'content' => [
            $this->paragraph('Vision loss is one of the most common disabilities worldwide, and it shapes how millions of people use the web. This article gives a short overview of the main types of visual impairment, their most frequent causes and the assistive technologies people rely on.'),

            $this->heading(2, 'What counts as a visual impairment'),
            $this->paragraph('A visual impairment is not only total blindness. It covers a wide range of conditions that reduce how clearly, how widely or how comfortably a person can see.'),
            $this->callout(
                CalloutVariant::Definition,
                'Visual impairment',
                'A condition that limits one or more functions of the visual system, such as visual acuity (sharpness), visual field (how much can be seen at once) or contrast sensitivity, in a way that affects everyday activities.',
            ),
            $this->callout(
                CalloutVariant::Information,
                'At least 2.2 billion people',
                "According to the World Health Organization's World report on vision (2019), at least 2.2 billion people globally have a near or distance vision impairment.",
            ),

            $this->heading(2, 'Common types'),
            $this->heading(3, 'Low vision'),
            $this->paragraph('People with low vision have some usable sight that glasses or contact lenses cannot fully correct. Many browse with screen magnification, larger text or high-contrast settings.'),
            $this->heading(3, 'Blindness'),
            $this->paragraph('People who are blind have little or no usable vision and typically use a screen reader with speech output, a refreshable braille display, or both.'),
            $this->heading(3, 'Colour vision deficiency'),
            $this->paragraph('Colour vision deficiency makes some colours hard to tell apart, most commonly red and green. It is far more common in men than in women.'),
            $this->callout(
                CalloutVariant::Example,
                'A form that only uses red',
                'A sign-up form marks invalid fields only with a red border. A user with red-green colour vision deficiency cannot tell which field is wrong. Adding an icon and a text message such as "Enter a valid email address" fixes this for everyone.',
            ),

            $this->heading(2, 'Common causes'),
            $this->paragraph('The World Health Organization lists uncorrected refractive errors and cataract as the leading causes of vision impairment, followed by conditions such as age-related macular degeneration, glaucoma and diabetic retinopathy.'),
            $this->callout(
                CalloutVariant::Important,
                'Much of it is preventable',
                "The same WHO report (2019) estimates that at least 1 billion of these cases could have been prevented or have yet to be addressed.\n\nFor the web, this means many readers live with vision loss that will not be corrected, so accessible design remains necessary.",
            ),

            $this->heading(2, 'Assistive technologies'),
            $this->heading(3, 'Screen readers'),
            $this->paragraph('Screen readers such as NVDA and JAWS on Windows, VoiceOver on Apple devices and TalkBack on Android read the page structure aloud. They depend on real headings, labelled form fields and text alternatives for images.'),
            $this->heading(3, 'Screen magnification'),
            $this->paragraph('Magnifiers enlarge part of the screen, sometimes many times over. Layouts that reflow cleanly when zoomed are essential for these users.'),
            $this->callout(
                CalloutVariant::Warning,
                'Do not rely on colour or images alone',
                'Information conveyed only by colour, or only inside an image without a text alternative, is lost for many readers. WCAG 2.1 addresses this in success criteria 1.4.1 (Use of Color) and 1.1.1 (Non-text Content).',
            ),
        ]];
    }

    /**
     * @return array<string, mixed>
     */
    private function heading(int $level, string $text): array
    {
        return ['type' => 'heading', 'attrs' => ['level' => $level], 'content' => [['type' => 'text', 'text' => $text]]];
    }

    /**
     * @return array<string, mixed>
     */
    private function paragraph(string $text): array
    {
        return ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]];
    }

    /**
     * @return array<string, mixed>
     */
    private function callout(CalloutVariant $variant, string $title, string $body): array
    {
        return ['type' => 'customBlock', 'attrs' => [
            'id' => 'callout',
            'config' => ['variant' => $variant->value, 'title' => $title, 'body' => $body],
        ]];
    }
}
