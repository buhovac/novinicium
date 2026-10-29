<?php

namespace App\Filament\Resources\Posts\Blocks;

use App\Models\Post;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;

/**
 * Image comparison: two images shown together (e.g. normal vision vs
 * cataract). Each image has its own required alt text and an optional
 * visible label; the pair can have a shared caption.
 *
 * The public renderer always shows both images side by side (stacked on
 * small screens). An optional slider view is a progressive enhancement on
 * top of that baseline, never a replacement for it.
 */
class ImageComparisonBlock extends PostContentBlock
{
    public const LABEL_MAX_LENGTH = 80;

    public const CAPTION_MAX_LENGTH = 300;

    /** @var list<string> */
    public const SIDES = ['first', 'second'];

    public static function getId(): string
    {
        return 'imageComparison';
    }

    public static function getLabel(): string
    {
        return 'Image comparison';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalWidth('3xl')
            ->modalDescription('Both images need their own alt text. Labels are short visible names, e.g. "Normal vision" and "Cataract".')
            ->schema([
                ...array_map(fn (string $side): Fieldset => Fieldset::make(ucfirst($side).' image')
                    ->schema([
                        ImageBlock::uploadField("{$side}_path")->columnSpanFull(),
                        TextInput::make("{$side}_alt")
                            ->label('Alt text')
                            ->required()
                            ->maxLength(ImageBlock::ALT_MAX_LENGTH)
                            ->helperText('Describe what this image shows.')
                            ->columnSpanFull(),
                        TextInput::make("{$side}_label")
                            ->label('Label (optional, visible)')
                            ->maxLength(self::LABEL_MAX_LENGTH)
                            ->columnSpanFull(),
                    ]), self::SIDES),
                TextInput::make('caption')
                    ->label('Caption (optional, visible, shared)')
                    ->maxLength(self::CAPTION_MAX_LENGTH),
            ]);
    }

    public static function rules(): array
    {
        $rules = ['caption' => ['nullable', 'string', 'max:'.self::CAPTION_MAX_LENGTH]];

        foreach (self::SIDES as $side) {
            $rules["{$side}_path"] = ['required', 'string', 'regex:'.ImageBlock::PATH_PATTERN];
            // Defence in depth: the form requires both alts too.
            $rules["{$side}_alt"] = ['required', 'string', 'max:'.ImageBlock::ALT_MAX_LENGTH];
            $rules["{$side}_label"] = ['nullable', 'string', 'max:'.self::LABEL_MAX_LENGTH];
        }

        return $rules;
    }

    /**
     * Text fields are trimmed so a whitespace-only alt counts as missing.
     */
    protected static function prepareConfig(array $config): array
    {
        foreach (['caption', 'first_alt', 'first_label', 'second_alt', 'second_label'] as $field) {
            if (is_string($config[$field] ?? null)) {
                $config[$field] = trim($config[$field]);
            }
        }

        return $config;
    }

    protected static function toContentNode(array $config): array
    {
        $node = [];

        foreach (self::SIDES as $side) {
            $node[$side] = [
                'src' => Post::storageUrlFor($config["{$side}_path"]),
                'alt' => $config["{$side}_alt"],
                'label' => ($config["{$side}_label"] ?? '') ?: null,
            ];
        }

        return [...$node, 'caption' => ($config['caption'] ?? '') ?: null];
    }

    public static function getPreviewLabel(array $config): string
    {
        $node = static::normalize($config);

        if ($node === null) {
            return 'Image comparison (incomplete)';
        }

        return sprintf(
            'Image comparison — %s / %s',
            $node['first']['label'] ?? $node['first']['alt'],
            $node['second']['label'] ?? $node['second']['alt'],
        );
    }

    public static function toPreviewHtml(array $config): ?string
    {
        $node = static::normalize($config);

        if ($node === null) {
            return '<p><em>This comparison is incomplete (missing an image or an alt text) and will not be shown. Click to edit.</em></p>';
        }

        $html = '';

        foreach (self::SIDES as $side) {
            $html .= sprintf(
                '<div style="flex: 1; min-width: 0;"><img src="%s" alt="" style="max-height: 6rem; width: auto;"><br><strong>%s</strong><br>Alt: %s</div>',
                e($node[$side]['src']),
                e($node[$side]['label'] ?? ucfirst($side).' image'),
                e($node[$side]['alt']),
            );
        }

        return '<div style="display: flex; gap: 1rem;">'.$html.'</div>';
    }

    /**
     * Snapshot fallback for `posts.body` only (rollback/RSS). The public site
     * renders the node through ArticleImageComparison.vue.
     */
    public static function toHtml(array $config, array $data): ?string
    {
        $node = static::normalize($config);

        if ($node === null) {
            return null;
        }

        $labels = implode(' / ', array_filter([$node['first']['label'], $node['second']['label']]));
        $caption = implode(' — ', array_filter([$labels, $node['caption']]));

        return sprintf(
            '<figure><img src="%s" alt="%s"><img src="%s" alt="%s">%s</figure>',
            e($node['first']['src']),
            e($node['first']['alt']),
            e($node['second']['src']),
            e($node['second']['alt']),
            $caption === '' ? '' : '<figcaption>'.e($caption).'</figcaption>',
        );
    }
}
