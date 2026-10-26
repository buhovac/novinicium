<?php

namespace App\Filament\Resources\Posts\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;

/**
 * Optional caption for a native table. Native TipTap tables have no caption
 * and custom blocks cannot wrap nested content, so the author places this
 * block directly above a table; the normalizer turns it into that table's
 * <caption>. A caption not followed by a table is dropped.
 */
class TableCaptionBlock extends PostContentBlock
{
    public const CAPTION_MAX_LENGTH = 200;

    public static function getId(): string
    {
        return 'tableCaption';
    }

    public static function getLabel(): string
    {
        return 'Table caption';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Place this block directly above a table. It becomes the table\'s caption and names it for screen readers.')
            ->schema([
                TextInput::make('caption')
                    ->required()
                    ->maxLength(self::CAPTION_MAX_LENGTH),
            ]);
    }

    public static function rules(): array
    {
        return [
            'caption' => ['required', 'string', 'max:'.self::CAPTION_MAX_LENGTH],
        ];
    }

    protected static function prepareConfig(array $config): array
    {
        if (is_string($config['caption'] ?? null)) {
            $config['caption'] = trim($config['caption']);
        }

        return $config;
    }

    protected static function toContentNode(array $config): array
    {
        return ['caption' => $config['caption']];
    }

    public static function getPreviewLabel(array $config): string
    {
        $node = static::normalize($config);

        return $node === null ? 'Table caption (empty)' : "Table caption — {$node['caption']}";
    }

    public static function toPreviewHtml(array $config): ?string
    {
        $node = static::normalize($config);

        if ($node === null) {
            return '<p><em>This caption is empty and will not be shown. Click to edit.</em></p>';
        }

        return sprintf(
            '<p><strong>Caption:</strong> %s</p><p><em>Applies to the table directly below.</em></p>',
            e($node['caption']),
        );
    }

    /**
     * Snapshot fallback for `posts.body` only (rollback/RSS).
     */
    public static function toHtml(array $config, array $data): ?string
    {
        $node = static::normalize($config);

        return $node === null ? null : '<p><strong>'.e($node['caption']).'</strong></p>';
    }
}
