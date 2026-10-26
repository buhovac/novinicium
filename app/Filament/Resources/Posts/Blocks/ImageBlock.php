<?php

namespace App\Filament\Resources\Posts\Blocks;

use App\Models\Post;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Validation\Rule;

/**
 * Image: an uploaded image with required alt text (unless explicitly marked
 * decorative), an optional visible caption and credit, and a display width.
 * Alt and caption are separate on purpose: alt replaces the image for screen
 * reader users, the caption adds information for everyone.
 */
class ImageBlock extends PostContentBlock
{
    public const ALT_MAX_LENGTH = 300;

    public const CAPTION_MAX_LENGTH = 300;

    public const CREDIT_MAX_LENGTH = 200;

    public const MAX_UPLOAD_KILOBYTES = 5120;

    /** @var list<string> */
    public const ACCEPTED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /** Only files the block uploaded itself: public disk, images/ folder, raster formats (no SVG). */
    public const PATH_PATTERN = '/^images\/[A-Za-z0-9][A-Za-z0-9._-]*\.(jpe?g|png|webp|gif)$/i';

    public static function getId(): string
    {
        return 'image';
    }

    public static function getLabel(): string
    {
        return 'Image';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Alt text describes the image for people who cannot see it. The caption is extra visible text.')
            ->schema([
                FileUpload::make('path')
                    ->label('Image')
                    ->image()
                    ->acceptedFileTypes(self::ACCEPTED_MIME_TYPES)
                    ->maxSize(self::MAX_UPLOAD_KILOBYTES)
                    ->disk('public')
                    ->directory('images')
                    ->visibility('public')
                    ->required()
                    ->helperText('JPEG, PNG, WebP or GIF, up to 5 MB.'),
                Toggle::make('decorative')
                    ->label('Decorative image')
                    ->helperText('Only for purely visual images that add no information. Screen readers will skip it.')
                    ->default(false)
                    ->live(),
                TextInput::make('alt')
                    ->label('Alt text')
                    ->required(fn (Get $get): bool => ! $get('decorative'))
                    ->hidden(fn (Get $get): bool => (bool) $get('decorative'))
                    ->maxLength(self::ALT_MAX_LENGTH)
                    ->helperText('Describe what the image shows and why it matters here.'),
                TextInput::make('caption')
                    ->label('Caption (optional, visible)')
                    ->hidden(fn (Get $get): bool => (bool) $get('decorative'))
                    ->maxLength(self::CAPTION_MAX_LENGTH),
                TextInput::make('credit')
                    ->label('Source / credit (optional)')
                    ->hidden(fn (Get $get): bool => (bool) $get('decorative'))
                    ->maxLength(self::CREDIT_MAX_LENGTH),
                Select::make('width')
                    ->label('Display width')
                    ->options(ImageWidth::options())
                    ->default(ImageWidth::Standard->value)
                    ->required(),
            ]);
    }

    public static function rules(): array
    {
        return [
            'path' => ['required', 'string', 'regex:'.self::PATH_PATTERN],
            'decorative' => ['required', 'boolean'],
            // Defence in depth: the form requires alt too, this catches anything else.
            'alt' => ['exclude_if:decorative,true', 'required', 'string', 'max:'.self::ALT_MAX_LENGTH],
            'caption' => ['exclude_if:decorative,true', 'nullable', 'string', 'max:'.self::CAPTION_MAX_LENGTH],
            'credit' => ['exclude_if:decorative,true', 'nullable', 'string', 'max:'.self::CREDIT_MAX_LENGTH],
            'width' => ['required', Rule::enum(ImageWidth::class)],
        ];
    }

    /**
     * Unknown width falls back to standard; decorative is strictly boolean.
     * Text fields are trimmed so whitespace-only alt counts as missing.
     */
    protected static function prepareConfig(array $config): array
    {
        $config['decorative'] = ($config['decorative'] ?? false) === true;

        $width = $config['width'] ?? null;

        if (! is_string($width) || ImageWidth::tryFrom($width) === null) {
            $config['width'] = ImageWidth::Standard->value;
        }

        foreach (['alt', 'caption', 'credit'] as $field) {
            if (is_string($config[$field] ?? null)) {
                $config[$field] = trim($config[$field]);
            }
        }

        return $config;
    }

    protected static function toContentNode(array $config): array
    {
        $isDecorative = $config['decorative'];

        return [
            'src' => Post::storageUrlFor($config['path']),
            'alt' => $isDecorative ? '' : $config['alt'],
            'decorative' => $isDecorative,
            'caption' => $isDecorative ? null : (($config['caption'] ?? '') ?: null),
            'credit' => $isDecorative ? null : (($config['credit'] ?? '') ?: null),
            'width' => $config['width'],
        ];
    }

    public static function getPreviewLabel(array $config): string
    {
        $node = static::normalize($config);

        if ($node === null) {
            return 'Image (incomplete)';
        }

        return $node['decorative'] ? 'Image — decorative' : "Image — {$node['alt']}";
    }

    public static function toPreviewHtml(array $config): ?string
    {
        $node = static::normalize($config);

        if ($node === null) {
            return '<p><em>This image is incomplete (missing file or alt text) and will not be shown. Click to edit.</em></p>';
        }

        return sprintf(
            '<p><img src="%s" alt="" style="max-height: 6rem; width: auto;"></p><p><strong>%s</strong> · %s</p>',
            e($node['src']),
            e($node['decorative'] ? 'Decorative' : 'Alt: '.$node['alt']),
            e(ImageWidth::from($node['width'])->label()),
        );
    }

    /**
     * Snapshot fallback for `posts.body` only (rollback/RSS). The public site
     * renders the node through ArticleImage.vue.
     */
    public static function toHtml(array $config, array $data): ?string
    {
        $node = static::normalize($config);

        if ($node === null) {
            return null;
        }

        $caption = implode(' — ', array_filter([$node['caption'], $node['credit']]));

        return sprintf(
            '<figure><img src="%s" alt="%s">%s</figure>',
            e($node['src']),
            e($node['alt']),
            $caption === '' ? '' : '<figcaption>'.e($caption).'</figcaption>',
        );
    }
}
