<?php

namespace App\Filament\Resources\Posts\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Statistics: a group of 1–3 prominent figures. Each figure is real text
 * (value, unit, label) that reads as a sentence, e.g. "2.2 billion people
 * live with a vision impairment", in the same order it is shown.
 */
class StatisticsBlock extends PostContentBlock
{
    public const MAX_ITEMS = 3;

    public const VALUE_MAX_LENGTH = 40;

    public const UNIT_MAX_LENGTH = 20;

    public const LABEL_MAX_LENGTH = 160;

    public const EXPLANATION_MAX_LENGTH = 300;

    public const SOURCE_MAX_LENGTH = 200;

    public static function getId(): string
    {
        return 'statistics';
    }

    public static function getLabel(): string
    {
        return 'Statistics';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalWidth('3xl')
            ->modalDescription('Write value + label so they read as one sentence, e.g. "2.2 billion" + "people live with a vision impairment".')
            ->schema([
                Repeater::make('items')
                    ->label('Statistics')
                    ->schema([
                        TextInput::make('value')
                            ->required()
                            ->maxLength(self::VALUE_MAX_LENGTH)
                            ->placeholder('2.2 billion, 8, 1 in 12'),
                        TextInput::make('unit')
                            ->label('Unit / suffix (optional)')
                            ->maxLength(self::UNIT_MAX_LENGTH)
                            ->placeholder('%'),
                        TextInput::make('label')
                            ->required()
                            ->maxLength(self::LABEL_MAX_LENGTH)
                            ->columnSpanFull(),
                        TextInput::make('explanation')
                            ->label('Explanation (optional)')
                            ->maxLength(self::EXPLANATION_MAX_LENGTH)
                            ->columnSpanFull(),
                        TextInput::make('source')
                            ->label('Source (optional)')
                            ->maxLength(self::SOURCE_MAX_LENGTH)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->minItems(1)
                    ->maxItems(self::MAX_ITEMS)
                    ->defaultItems(1)
                    ->required(),
                Select::make('layout')
                    ->options(StatisticsLayout::options())
                    ->default(StatisticsLayout::ThreeColumns->value)
                    ->required(),
            ]);
    }

    public static function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:'.self::MAX_ITEMS],
            'items.*' => ['array'],
            ...self::itemRules('items.*.'),
            'layout' => ['required', Rule::enum(StatisticsLayout::class)],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private static function itemRules(string $prefix = ''): array
    {
        return [
            $prefix.'value' => ['required', 'string', 'max:'.self::VALUE_MAX_LENGTH],
            $prefix.'unit' => ['nullable', 'string', 'max:'.self::UNIT_MAX_LENGTH],
            $prefix.'label' => ['required', 'string', 'max:'.self::LABEL_MAX_LENGTH],
            $prefix.'explanation' => ['nullable', 'string', 'max:'.self::EXPLANATION_MAX_LENGTH],
            $prefix.'source' => ['nullable', 'string', 'max:'.self::SOURCE_MAX_LENGTH],
        ];
    }

    /**
     * Invalid items are dropped individually (the rest still render), the
     * list is capped at three, and an unknown layout follows the item count.
     * Repeater items are keyed by UUID, so the list is re-indexed.
     */
    protected static function prepareConfig(array $config): array
    {
        $items = is_array($config['items'] ?? null) ? array_values($config['items']) : [];

        $items = array_map(fn (mixed $item): mixed => is_array($item) ? array_map(
            fn (mixed $value): mixed => is_string($value) ? trim($value) : $value,
            $item,
        ) : $item, $items);

        $items = array_values(array_filter(
            $items,
            fn (mixed $item): bool => is_array($item) && Validator::make($item, self::itemRules())->passes(),
        ));

        $config['items'] = array_slice($items, 0, self::MAX_ITEMS);

        $layout = $config['layout'] ?? null;

        if (! is_string($layout) || StatisticsLayout::tryFrom($layout) === null) {
            $config['layout'] = StatisticsLayout::forItemCount(count($config['items']))->value;
        }

        return $config;
    }

    protected static function toContentNode(array $config): array
    {
        return [
            'layout' => $config['layout'],
            'items' => array_map(fn (array $item): array => [
                'value' => $item['value'],
                'unit' => ($item['unit'] ?? '') ?: null,
                'label' => $item['label'],
                'explanation' => ($item['explanation'] ?? '') ?: null,
                'source' => ($item['source'] ?? '') ?: null,
            ], $config['items']),
        ];
    }

    public static function getPreviewLabel(array $config): string
    {
        $node = static::normalize($config);

        if ($node === null) {
            return 'Statistics (incomplete)';
        }

        $count = count($node['items']);

        return 'Statistics — '.$count.' '.($count === 1 ? 'figure' : 'figures');
    }

    public static function toPreviewHtml(array $config): ?string
    {
        $node = static::normalize($config);

        if ($node === null) {
            return '<p><em>These statistics are incomplete (each needs a value and a label) and will not be shown. Click to edit.</em></p>';
        }

        return '<ul>'.implode('', array_map(
            fn (array $item): string => '<li><strong>'.e($item['value'].($item['unit'] ?? '')).'</strong> '.e($item['label']).'</li>',
            $node['items'],
        )).'</ul>';
    }

    /**
     * Snapshot fallback for `posts.body` only (rollback/RSS). The public site
     * renders the node through ArticleStatistics.vue.
     */
    public static function toHtml(array $config, array $data): ?string
    {
        $node = static::normalize($config);

        if ($node === null) {
            return null;
        }

        return '<ul>'.implode('', array_map(function (array $item): string {
            $details = implode(' ', array_filter([
                $item['explanation'],
                $item['source'] === null ? null : 'Source: '.$item['source'],
            ]));

            return '<li><strong>'.e($item['value'].($item['unit'] ?? '')).'</strong> '.e($item['label'])
                .($details === '' ? '' : ' — '.e($details)).'</li>';
        }, $node['items'])).'</ul>';
    }
}
