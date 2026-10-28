<?php

namespace Database\Seeders;

use App\Filament\Resources\Posts\Blocks\CalloutVariant;
use App\Filament\Resources\Posts\Blocks\ChartType;
use App\Filament\Resources\Posts\Blocks\CodeLanguage;
use App\Filament\Resources\Posts\Blocks\ImageWidth;
use App\Filament\Resources\Posts\Blocks\StatisticsLayout;
use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
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

    public const CONTRAST_IMAGE = 'images/demo-contrast-comparison.png';

    public const DIVIDER_IMAGE = 'images/demo-divider.png';

    public const ZOOM_IMAGE = 'images/demo-zoom.png';

    public const VISION_NORMAL_IMAGE = 'images/demo-vision-normal.png';

    public const VISION_CATARACT_IMAGE = 'images/demo-vision-cataract.png';

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DemoArticleSeeder is for local development only.');
        }

        $this->storeDemoImages();

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

            $this->paragraph('Most people with vision loss are not blind. A large study of the global burden of disease estimated how many people lived with each level of vision loss in 2020:'),
            $this->chart(
                ChartType::Bar,
                title: 'Global vision loss by severity, 2020',
                categoryHeading: 'Level of vision loss',
                seriesName: 'People (estimate)',
                rows: [
                    'Blindness' => 43.3,
                    'Moderate or severe distance vision impairment' => 295,
                    'Mild distance vision impairment' => 258,
                    'Near vision impairment (uncorrected presbyopia)' => 510,
                ],
                unit: 'million',
                source: 'GBD 2019 Blindness and Vision Impairment Collaborators, The Lancet Global Health (2021)',
                caption: 'Central estimates for 2020. These come from a different study than the WHO figure of at least 2.2 billion and use different definitions, so the numbers do not add up to it.',
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

            $this->image(
                self::CONTRAST_IMAGE,
                alt: 'Two samples of the letters Aa on a white background: on the left in dark grey, clearly readable; on the right in pale grey, barely visible.',
                caption: 'Dark grey #353535 on white reaches a contrast ratio of about 12.6:1. Pale grey #C8C8C8 on white reaches only about 1.7:1, far below the 4.5:1 that WCAG 2.1 level AA requires for normal text.',
                credit: 'Illustration: Nov Inicium, generated for this demo article.',
                width: ImageWidth::Wide,
            ),

            $this->heading(2, 'Common causes'),
            $this->paragraph('The World Health Organization lists uncorrected refractive errors and cataract as the leading causes of vision impairment, followed by conditions such as age-related macular degeneration, glaucoma and diabetic retinopathy.'),
            $this->paragraph('A cataract is a clouding of the lens inside the eye. It usually develops slowly and makes vision blurry or hazy, with colours that look faded or yellowish.'),
            $this->imageComparison(
                first: [
                    'path' => self::VISION_NORMAL_IMAGE,
                    'alt' => 'A simple eye chart, sharp and high-contrast: rows of dark grey letters getting smaller from top to bottom, above three bright squares in red, blue and lime green.',
                    'label' => 'Normal vision',
                ],
                second: [
                    'path' => self::VISION_CATARACT_IMAGE,
                    'alt' => 'The same eye chart blurred and washed out with a yellowish haze: the large letters at the top can still be made out, the smaller rows below cannot, and the red, blue and green squares look dull and muddy.',
                    'label' => 'Simulated cataract',
                ],
                caption: 'A rough simulation for illustration only. How a cataract affects sight varies from person to person and changes as it develops.',
            ),
            $this->callout(
                CalloutVariant::Important,
                'Much of it is preventable',
                "The same WHO report (2019) estimates that at least 1 billion of these cases could have been prevented or have yet to be addressed.\n\nFor the web, this means many readers live with vision loss that will not be corrected, so accessible design remains necessary.",
            ),
            $this->statistics(StatisticsLayout::ThreeColumns, [
                ['value' => '2.2 billion', 'label' => 'people have a near or distance vision impairment', 'explanation' => 'A minimum estimate ("at least").', 'source' => 'WHO, World report on vision (2019)'],
                ['value' => '1 billion', 'label' => 'of these cases could have been prevented or are not yet addressed', 'explanation' => 'Also a minimum estimate.', 'source' => 'WHO, World report on vision (2019)'],
                ['value' => '4.5', 'unit' => ':1', 'label' => 'minimum contrast ratio for normal-size text', 'explanation' => 'Large text needs at least 3:1.', 'source' => 'WCAG 2.1, success criterion 1.4.3 (level AA)'],
            ]),

            $this->image(self::DIVIDER_IMAGE, decorative: true),

            $this->heading(2, 'Assistive technologies'),
            $this->heading(3, 'Screen readers'),
            $this->paragraph('Screen readers such as NVDA and JAWS on Windows, VoiceOver on Apple devices and TalkBack on Android read the page structure aloud. They depend on real headings, labelled form fields and text alternatives for images.'),
            $this->paragraph('Screen readers announce an image through its alt text, and a visible caption through the figure caption. They do different jobs, as this markup shows:'),
            $this->code(CodeLanguage::Html, <<<'HTML'
                <figure>
                  <img src="contrast.png"
                       alt="Letters Aa in dark grey and in pale grey on white">
                  <figcaption>Pale grey text fails WCAG contrast.</figcaption>
                </figure>

                <!-- Purely decorative: empty alt, so screen readers skip it -->
                <img src="divider.png" alt="">
                HTML),

            $this->heading(3, 'Screen magnification'),
            $this->columns([
                [
                    $this->paragraph('Magnifiers enlarge part of the screen, sometimes many times over. Layouts that reflow cleanly when zoomed are essential for these users.'),
                    $this->paragraph('When text is enlarged, a fixed-width layout forces readers to scroll sideways on every line. WCAG 2.1 asks for content that reflows at 400% zoom (success criterion 1.4.10).'),
                ],
                [
                    $this->image(
                        self::ZOOM_IMAGE,
                        alt: 'The word Zoom shown twice: small at normal size on the left, and enlarged four times on the right.',
                        caption: 'The same word at 100% and at 400% magnification.',
                        credit: 'Illustration: Nov Inicium, generated for this demo article.',
                    ),
                ],
            ]),
            $this->callout(
                CalloutVariant::Warning,
                'Do not rely on colour or images alone',
                'Information conveyed only by colour, or only inside an image without a text alternative, is lost for many readers. WCAG 2.1 addresses this in success criteria 1.4.1 (Use of Color) and 1.1.1 (Non-text Content).',
            ),

            $this->heading(2, 'Common barriers and how to remove them'),
            $this->paragraph('Most barriers come from a handful of design decisions. The table pairs each one with the people it affects most and the adaptation that removes it.'),
            $this->tableCaption('Functional barriers and adaptations for readers with visual impairments'),
            $this->table(
                ['Barrier', 'Who is most affected', 'Adaptation'],
                [
                    ['Small or low-contrast text', 'People with low vision', 'Contrast of at least 4.5:1 and text that can be resized (WCAG 1.4.3, 1.4.4)'],
                    ['Information shown only by colour', 'People with colour vision deficiency', 'Add text labels, icons or patterns (WCAG 1.4.1)'],
                    ['Images without text alternatives', 'Screen reader users', 'Meaningful alt text, or empty alt for decorative images (WCAG 1.1.1)'],
                    ['Layouts that break when zoomed', 'Screen magnifier users', 'Content that reflows at 400% zoom (WCAG 1.4.10)'],
                    ['Controls that only work with a mouse', 'Screen reader and keyboard users', 'Full keyboard operation with visible focus (WCAG 2.1.1, 2.4.7)'],
                ],
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
    private function image(
        string $path,
        string $alt = '',
        ?string $caption = null,
        ?string $credit = null,
        ImageWidth $width = ImageWidth::Standard,
        bool $decorative = false,
    ): array {
        return ['type' => 'customBlock', 'attrs' => [
            'id' => 'image',
            'config' => [
                'path' => $path,
                'decorative' => $decorative,
                'alt' => $alt,
                'caption' => $caption,
                'credit' => $credit,
                'width' => $width->value,
            ],
        ]];
    }

    /**
     * @param  array{path: string, alt: string, label: string}  $first
     * @param  array{path: string, alt: string, label: string}  $second
     * @return array<string, mixed>
     */
    private function imageComparison(array $first, array $second, ?string $caption = null): array
    {
        $config = ['caption' => $caption];

        foreach (['first' => $first, 'second' => $second] as $side => $image) {
            foreach ($image as $field => $value) {
                $config["{$side}_{$field}"] = $value;
            }
        }

        return ['type' => 'customBlock', 'attrs' => ['id' => 'imageComparison', 'config' => $config]];
    }

    /**
     * Single-series chart; rows map category label => value.
     *
     * @param  array<string, int|float>  $rows
     * @return array<string, mixed>
     */
    private function chart(
        ChartType $type,
        string $title,
        string $categoryHeading,
        string $seriesName,
        array $rows,
        ?string $unit = null,
        ?string $source = null,
        ?string $caption = null,
    ): array {
        return ['type' => 'customBlock', 'attrs' => [
            'id' => 'chart',
            'config' => [
                'title' => $title,
                'type' => $type->value,
                'category_heading' => $categoryHeading,
                'series_1' => $seriesName,
                'series_2' => null,
                'series_3' => null,
                'rows' => array_map(
                    fn (string $label, int|float $value): array => ['label' => $label, 'value_1' => $value],
                    array_keys($rows),
                    $rows,
                ),
                'unit' => $unit,
                'source' => $source,
                'caption' => $caption,
            ],
        ]];
    }

    /**
     * @param  list<array<string, string>>  $items
     * @return array<string, mixed>
     */
    private function statistics(StatisticsLayout $layout, array $items): array
    {
        return ['type' => 'customBlock', 'attrs' => [
            'id' => 'statistics',
            'config' => ['layout' => $layout->value, 'items' => $items],
        ]];
    }

    /**
     * Native RichEditor grid: one entry per column, each a list of nodes.
     *
     * @param  list<list<array<string, mixed>>>  $columns
     * @return array<string, mixed>
     */
    private function columns(array $columns): array
    {
        return [
            'type' => 'grid',
            'attrs' => ['data-cols' => (string) count($columns), 'data-from-breakpoint' => 'md'],
            'content' => array_map(fn (array $content): array => [
                'type' => 'gridColumn',
                'attrs' => ['data-col-span' => '1'],
                'content' => $content,
            ], $columns),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tableCaption(string $caption): array
    {
        return ['type' => 'customBlock', 'attrs' => ['id' => 'tableCaption', 'config' => ['caption' => $caption]]];
    }

    /**
     * Native RichEditor table: a header row, then rows whose first cell is a
     * row header.
     *
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     * @return array<string, mixed>
     */
    private function table(array $headers, array $rows): array
    {
        $cell = fn (string $type, string $text): array => [
            'type' => $type,
            'attrs' => ['colspan' => 1, 'rowspan' => 1, 'colwidth' => null],
            'content' => [$this->paragraph($text)],
        ];

        return [
            'type' => 'table',
            'content' => [
                ['type' => 'tableRow', 'content' => array_map(fn (string $text): array => $cell('tableHeader', $text), $headers)],
                ...array_map(fn (array $row): array => [
                    'type' => 'tableRow',
                    'content' => array_map(
                        fn (string $text, int $index): array => $cell($index === 0 ? 'tableHeader' : 'tableCell', $text),
                        $row,
                        array_keys($row),
                    ),
                ], $rows),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function code(CodeLanguage $language, string $code): array
    {
        return ['type' => 'customBlock', 'attrs' => [
            'id' => 'code',
            'config' => ['language' => $language->value, 'code' => $code],
        ]];
    }

    /**
     * Generate the demo images on the public disk (overwritten on each run).
     */
    private function storeDemoImages(): void
    {
        Storage::disk('public')->put(self::CONTRAST_IMAGE, $this->contrastImagePng());
        Storage::disk('public')->put(self::DIVIDER_IMAGE, $this->dividerImagePng());
        Storage::disk('public')->put(self::ZOOM_IMAGE, $this->zoomImagePng());
        Storage::disk('public')->put(self::VISION_NORMAL_IMAGE, $this->toPng($this->eyeChartImage()));
        Storage::disk('public')->put(self::VISION_CATARACT_IMAGE, $this->toPng($this->cataractSimulation($this->eyeChartImage())));
    }

    /**
     * 800x500 eye chart: letter rows shrinking downwards, then red, blue and
     * lime squares, dark grey on white.
     */
    private function eyeChartImage(): \GdImage
    {
        $image = imagecreatetruecolor(800, 500);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 255, 255, 255));

        $rows = [['E', 8, 20], ['F P', 5, 155], ['T O Z', 4, 245], ['L P E D', 3, 315], ['P E C F D', 2, 368]];

        foreach ($rows as [$letters, $scale, $top]) {
            $width = strlen($letters) * 9;
            $text = imagecreatetruecolor($width, 15);
            imagefill($text, 0, 0, (int) imagecolorallocate($text, 255, 255, 255));
            imagestring($text, 5, 0, 0, $letters, (int) imagecolorallocate($text, 0x35, 0x35, 0x35));
            imagecopyresized($image, $text, (int) ((800 - $width * $scale) / 2), $top, 0, 0, $width * $scale, 15 * $scale, $width, 15);
        }

        foreach ([[0xD7, 0x26, 0x26], [0x25, 0x63, 0xEB], [0xBE, 0xEF, 0x35]] as $index => [$red, $green, $blue]) {
            $left = 220 + $index * 130;
            imagefilledrectangle($image, $left, 410, $left + 100, 480, (int) imagecolorallocate($image, $red, $green, $blue));
        }

        return $image;
    }

    /**
     * Approximate cataract on the 800x500 eye chart: heavy blur, lower
     * contrast, a yellowish haze.
     */
    private function cataractSimulation(\GdImage $image): \GdImage
    {
        // Shrink to 1/10 and scale back up: a strong, cheap blur.
        $small = imagecreatetruecolor(80, 50);
        imagecopyresampled($small, $image, 0, 0, 0, 0, 80, 50, 800, 500);
        imagecopyresampled($image, $small, 0, 0, 0, 0, 800, 500, 80, 50);

        for ($pass = 0; $pass < 15; $pass++) {
            imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
        }

        imagefilter($image, IMG_FILTER_CONTRAST, 35);
        imagefilter($image, IMG_FILTER_COLORIZE, 18, 10, -30);

        return $image;
    }

    /**
     * 800x400 PNG: "Zoom" at 1x (left) and 4x (right), dark grey on white.
     */
    private function zoomImagePng(): string
    {
        $text = imagecreatetruecolor(36, 16);
        imagefill($text, 0, 0, (int) imagecolorallocate($text, 255, 255, 255));
        imagestring($text, 5, 0, 0, 'Zoom', (int) imagecolorallocate($text, 0x35, 0x35, 0x35));

        $image = imagecreatetruecolor(800, 400);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 255, 255, 255));
        imagecopyresized($image, $text, 110, 192, 0, 0, 36 * 2, 16 * 2, 36, 16);
        imagecopyresized($image, $text, 400, 136, 0, 0, 36 * 8, 16 * 8, 36, 16);
        imageline($image, 330, 40, 330, 360, (int) imagecolorallocate($image, 0xE5, 0xE5, 0xE5));

        return $this->toPng($image);
    }

    /**
     * 1200x500 PNG: "Aa" in #353535 (left) and #C8C8C8 (right) on white.
     */
    private function contrastImagePng(): string
    {
        $scale = 10;
        $small = imagecreatetruecolor(120, 50);
        imagefill($small, 0, 0, (int) imagecolorallocate($small, 255, 255, 255));
        imagestring($small, 5, 21, 17, 'Aa', (int) imagecolorallocate($small, 0x35, 0x35, 0x35));
        imagestring($small, 5, 81, 17, 'Aa', (int) imagecolorallocate($small, 0xC8, 0xC8, 0xC8));
        imageline($small, 60, 6, 60, 43, (int) imagecolorallocate($small, 0xE5, 0xE5, 0xE5));

        $image = imagecreatetruecolor(120 * $scale, 50 * $scale);
        imagecopyresized($image, $small, 0, 0, 0, 0, 120 * $scale, 50 * $scale, 120, 50);

        return $this->toPng($image);
    }

    /**
     * 1200x60 PNG: a brand-green band with grey stripes (purely decorative).
     */
    private function dividerImagePng(): string
    {
        $image = imagecreatetruecolor(1200, 60);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 0xBE, 0xEF, 0x35));
        $grey = (int) imagecolorallocate($image, 0x35, 0x35, 0x35);

        for ($x = -60; $x < 1200; $x += 40) {
            imagefilledpolygon($image, [$x, 60, $x + 12, 60, $x + 72, 0, $x + 60, 0], $grey);
        }

        return $this->toPng($image);
    }

    private function toPng(\GdImage $image): string
    {
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
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
