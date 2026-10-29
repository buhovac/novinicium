<?php

namespace App\Filament\Resources\Posts\Blocks;

/**
 * Single registry of blog custom blocks, shared by the editor (PostForm),
 * the HTML snapshot (Post model) and the public normalizer (BuildPostContent).
 */
class PostContentBlocks
{
    /**
     * @return list<class-string<PostContentBlock>>
     */
    public static function all(): array
    {
        return [
            CalloutBlock::class,
            ImageBlock::class,
            CodeBlock::class,
            TableCaptionBlock::class,
            StatisticsBlock::class,
            ImageComparisonBlock::class,
            ChartBlock::class,
            QuizBlock::class,
        ];
    }

    /**
     * @return class-string<PostContentBlock>|null
     */
    public static function find(mixed $id): ?string
    {
        foreach (static::all() as $block) {
            if ($block::getId() === $id) {
                return $block;
            }
        }

        return null;
    }

    /**
     * Remove custom block nodes that could not render (unknown id, config not
     * an array) so Filament's HTML renderer never receives them.
     *
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    public static function withoutUnusableBlocks(array $node): array
    {
        if (! isset($node['content']) || ! is_array($node['content'])) {
            return $node;
        }

        $node['content'] = array_values(array_map(
            fn (array $child): array => static::withoutUnusableBlocks($child),
            array_filter($node['content'], fn (mixed $child): bool => is_array($child) && (
                ($child['type'] ?? null) !== 'customBlock'
                || (static::find($child['attrs']['id'] ?? null) !== null && is_array($child['attrs']['config'] ?? null))
            )),
        ));

        return $node;
    }
}
