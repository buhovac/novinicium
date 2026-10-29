<?php

namespace App\Filament\Resources\Posts\Blocks;

use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Illuminate\Support\Facades\Validator;

/**
 * The contract every blog custom block follows. One class owns:
 *
 *  - the editor side: getId(), getLabel(), configureEditorAction() (modal form),
 *    getPreviewLabel() / toPreviewHtml() (a preview card, never a full render);
 *  - the config schema: rules(), used to validate stored config on the server;
 *  - the public node: toContentNode(), the typed data <ContentNode> renders;
 *  - the HTML snapshot: toHtml(), a minimal semantic fallback written into
 *    `posts.body` for rollback/RSS. The public site never uses it.
 *
 * Register new blocks in PostContentBlocks::all().
 */
abstract class PostContentBlock extends RichContentCustomBlock
{
    /**
     * Laravel validation rules for the stored block config.
     *
     * @return array<string, mixed>
     */
    abstract public static function rules(): array;

    /**
     * Build the public node from config that already passed rules().
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    abstract protected static function toContentNode(array $config): array;

    /**
     * Fix up recoverable config before validation (e.g. fall back to a
     * default for an unknown option). Unrecoverable config fails rules().
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected static function prepareConfig(array $config): array
    {
        return $config;
    }

    /**
     * Validated, typed public node, or null when the config is unusable
     * (the block is then dropped without affecting the rest of the article).
     *
     * @return array<string, mixed>|null
     */
    public static function normalize(mixed $config): ?array
    {
        if (! is_array($config)) {
            return null;
        }

        $validator = Validator::make(static::prepareConfig($config), static::rules());

        if ($validator->fails()) {
            return null;
        }

        return ['type' => static::getId(), ...static::toContentNode($validator->validated())];
    }
}
