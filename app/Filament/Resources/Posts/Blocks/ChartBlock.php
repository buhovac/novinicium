<?php

namespace App\Filament\Resources\Posts\Blocks;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Chart: structured data (categories × up to three numeric series), never
 * an image. The public renderer always shows the data as an accessible
 * table; the drawn chart is a supplementary visual on top of it.
 *
 * Stored config: title, type, category_heading, series_1..3 (names; the
 * first is required, empty names are unused), rows (label + value_1..3),
 * unit, source, caption. Values of unused series are ignored. Values are
 * validated as finite numbers; if any
 * row is unusable the whole block is dropped rather than shown with a
 * silently missing category.
 */
class ChartBlock extends PostContentBlock
{
    public const MAX_SERIES = 3;

    public const MAX_ROWS = 24;

    public const TITLE_MAX_LENGTH = 150;

    public const NAME_MAX_LENGTH = 80;

    public const UNIT_MAX_LENGTH = 20;

    public const SOURCE_MAX_LENGTH = 200;

    public const CAPTION_MAX_LENGTH = 300;

    public const DEFAULT_CATEGORY_HEADING = 'Category';

    public static function getId(): string
    {
        return 'chart';
    }

    public static function getLabel(): string
    {
        return 'Chart';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalWidth('5xl')
            ->modalDescription('Enter the data, not a picture: readers always get it as a table, and the chart is drawn from the same numbers.')
            ->schema([
                TextInput::make('title')
                    ->label('Chart title')
                    ->required()
                    ->maxLength(self::TITLE_MAX_LENGTH)
                    ->helperText('Also the caption of the data table, e.g. "Global vision loss by severity, 2020".'),
                Select::make('type')
                    ->label('Chart type')
                    ->options(ChartType::options())
                    ->default(ChartType::Bar->value)
                    ->required()
                    ->live(),
                Fieldset::make('Series')
                    ->schema([
                        TextInput::make('category_heading')
                            ->label('Category column heading (optional)')
                            ->placeholder(self::DEFAULT_CATEGORY_HEADING)
                            ->maxLength(self::NAME_MAX_LENGTH),
                        TextInput::make('series_1')
                            ->label('Series 1 name')
                            ->required()
                            ->maxLength(self::NAME_MAX_LENGTH)
                            ->placeholder('People'),
                        ...array_map(fn (int $series): TextInput => TextInput::make("series_{$series}")
                            ->label("Series {$series} name (optional)")
                            ->maxLength(self::NAME_MAX_LENGTH)
                            ->helperText('Bar and line only.')
                            ->live(onBlur: true)
                            ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                if (filled($value) && ChartType::tryFrom((string) $get('type'))?->isCircular()) {
                                    $fail('Pie and doughnut charts show one series. Clear this name or choose bar or line.');
                                }
                            }), range(2, self::MAX_SERIES)),
                    ])
                    ->columns(2),
                Repeater::make('rows')
                    ->label('Data')
                    ->table([
                        TableColumn::make('Category')->markAsRequired(),
                        ...array_map(fn (int $series): TableColumn => TableColumn::make("Series {$series} value"), range(1, self::MAX_SERIES)),
                    ])
                    ->schema([
                        TextInput::make('label')
                            ->required()
                            ->maxLength(self::NAME_MAX_LENGTH),
                        ...array_map(fn (int $series): TextInput => TextInput::make("value_{$series}")
                            ->numeric()
                            ->required(fn (Get $get): bool => $series === 1 || filled($get("../../series_{$series}")))
                            ->minValue(fn (Get $get): ?int => ChartType::tryFrom((string) $get('../../type'))?->isCircular() ? 0 : null), range(1, self::MAX_SERIES)),
                    ])
                    ->minItems(1)
                    ->maxItems(self::MAX_ROWS)
                    ->defaultItems(2)
                    ->required(),
                TextInput::make('unit')
                    ->label('Unit / suffix (optional)')
                    ->maxLength(self::UNIT_MAX_LENGTH)
                    ->placeholder('million, %'),
                TextInput::make('source')
                    ->label('Source (optional)')
                    ->maxLength(self::SOURCE_MAX_LENGTH),
                TextInput::make('caption')
                    ->label('Explanation (optional, visible)')
                    ->maxLength(self::CAPTION_MAX_LENGTH),
            ]);
    }

    public static function rules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:'.self::TITLE_MAX_LENGTH],
            'type' => ['required', 'string'],
            'category_heading' => ['nullable', 'string', 'max:'.self::NAME_MAX_LENGTH],
            'series_1' => ['required', 'string', 'max:'.self::NAME_MAX_LENGTH],
            'rows' => ['required', 'array', 'min:1', 'max:'.self::MAX_ROWS],
            'rows.*' => ['required', 'array'],
            'rows.*.label' => ['required', 'string', 'max:'.self::NAME_MAX_LENGTH],
            'rows.*.value_1' => ['required', 'numeric', self::finite(...)],
            'unit' => ['nullable', 'string', 'max:'.self::UNIT_MAX_LENGTH],
            'source' => ['nullable', 'string', 'max:'.self::SOURCE_MAX_LENGTH],
            'caption' => ['nullable', 'string', 'max:'.self::CAPTION_MAX_LENGTH],
        ];

        foreach (range(2, self::MAX_SERIES) as $series) {
            $rules["series_{$series}"] = ['nullable', 'string', 'max:'.self::NAME_MAX_LENGTH];
            $rules["rows.*.value_{$series}"] = ["required_with:series_{$series}", 'nullable', 'numeric', self::finite(...)];
        }

        return $rules;
    }

    private static function finite(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_numeric($value) && ! is_finite((float) $value)) {
            $fail('The value must be a finite number.');
        }
    }

    /**
     * Trims text, re-indexes the UUID-keyed repeater rows and turns empty
     * optional series names into null. Unknown types fall back to bar, and
     * pie/doughnut fall back to bar when the data cannot be a pie (several
     * series, negative values or a zero total): the table stays complete.
     */
    protected static function prepareConfig(array $config): array
    {
        $config = array_map(fn (mixed $value): mixed => is_string($value) ? trim($value) : $value, $config);

        foreach (range(2, self::MAX_SERIES) as $series) {
            $config["series_{$series}"] = ($config["series_{$series}"] ?? null) ?: null;
        }

        $unusedValueKeys = array_map(
            fn (int $series): string => "value_{$series}",
            array_filter(range(2, self::MAX_SERIES), fn (int $series): bool => $config["series_{$series}"] === null),
        );

        if (is_array($config['rows'] ?? null)) {
            $config['rows'] = array_values(array_map(fn (mixed $row): mixed => is_array($row)
                ? array_diff_key(
                    array_map(fn (mixed $value): mixed => is_string($value) ? trim($value) : $value, $row),
                    array_flip($unusedValueKeys),
                )
                : $row, $config['rows']));
        }

        $type = ChartType::tryFrom(is_string($config['type'] ?? null) ? $config['type'] : '') ?? ChartType::Bar;

        if ($type->isCircular() && ! self::canBeCircular($config)) {
            $type = ChartType::Bar;
        }

        $config['type'] = $type->value;

        return $config;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function canBeCircular(array $config): bool
    {
        if (($config['series_2'] ?? null) !== null || ($config['series_3'] ?? null) !== null || ! is_array($config['rows'] ?? null)) {
            return false;
        }

        $values = array_map(fn (mixed $row): mixed => is_array($row) ? ($row['value_1'] ?? null) : null, $config['rows']);
        $numbers = array_filter($values, fn (mixed $value): bool => is_numeric($value));

        return count($numbers) === count($values)
            && min(array_map(floatval(...), $numbers) ?: [0]) >= 0
            && array_sum(array_map(floatval(...), $numbers)) > 0;
    }

    protected static function toContentNode(array $config): array
    {
        $unit = ($config['unit'] ?? '') ?: null;
        $series = [];

        foreach (range(1, self::MAX_SERIES) as $index) {
            $name = $config["series_{$index}"] ?? null;

            if ($name === null) {
                continue;
            }

            $values = array_map(fn (array $row): int|float => self::toNumber($row["value_{$index}"]), $config['rows']);

            $series[] = [
                'name' => $name,
                'values' => $values,
                'display' => array_map(fn (int|float $value): string => self::formatValue($value, $unit), $values),
            ];
        }

        return [
            'chartType' => $config['type'],
            'title' => $config['title'],
            'categoryHeading' => ($config['category_heading'] ?? '') ?: self::DEFAULT_CATEGORY_HEADING,
            'categories' => array_map(fn (array $row): string => $row['label'], $config['rows']),
            'series' => $series,
            'unit' => $unit,
            'source' => ($config['source'] ?? '') ?: null,
            'caption' => ($config['caption'] ?? '') ?: null,
        ];
    }

    private static function toNumber(mixed $value): int|float
    {
        $number = (float) $value;

        return floor($number) === $number && abs($number) < PHP_INT_MAX ? (int) $number : $number;
    }

    /**
     * "43.3 million", "1,234", "12%": a word unit gets a space, a symbol does not.
     */
    public static function formatValue(int|float $value, ?string $unit): string
    {
        $number = is_int($value)
            ? number_format($value)
            : rtrim(rtrim(number_format($value, 6), '0'), '.');

        if ($unit === null) {
            return $number;
        }

        return preg_match('/^\p{L}/u', $unit) === 1 ? "{$number} {$unit}" : $number.$unit;
    }

    public static function getPreviewLabel(array $config): string
    {
        $node = static::normalize($config);

        if ($node === null) {
            return 'Chart (incomplete)';
        }

        return "Chart — {$node['title']}";
    }

    public static function toPreviewHtml(array $config): ?string
    {
        $node = static::normalize($config);

        if ($node === null) {
            return '<p><em>This chart is incomplete (it needs a title, a series name and a number for every category) and will not be shown. Click to edit.</em></p>';
        }

        $seriesCount = count($node['series']);
        $categoryCount = count($node['categories']);

        return sprintf(
            '<p><strong>%s</strong></p><p>%s chart · %d series · %d %s: %s</p>',
            e($node['title']),
            e(ChartType::from($node['chartType'])->label()),
            $seriesCount,
            $categoryCount,
            $categoryCount === 1 ? 'category' : 'categories',
            e(implode(', ', $node['categories'])),
        );
    }

    /**
     * Snapshot fallback for `posts.body` only (rollback/RSS): the data table.
     * The public site renders the node through ArticleChart.vue.
     */
    public static function toHtml(array $config, array $data): ?string
    {
        $node = static::normalize($config);

        if ($node === null) {
            return null;
        }

        $head = '<th scope="col">'.e($node['categoryHeading']).'</th>'
            .implode('', array_map(fn (array $series): string => '<th scope="col">'.e($series['name']).'</th>', $node['series']));

        $body = '';

        foreach ($node['categories'] as $index => $category) {
            $body .= '<tr><th scope="row">'.e($category).'</th>'
                .implode('', array_map(fn (array $series): string => '<td>'.e($series['display'][$index]).'</td>', $node['series']))
                .'</tr>';
        }

        $notes = implode(' ', array_filter([$node['caption'], $node['source'] === null ? null : 'Source: '.$node['source']]));

        return '<table><caption>'.e($node['title']).'</caption><thead><tr>'.$head.'</tr></thead><tbody>'.$body.'</tbody></table>'
            .($notes === '' ? '' : '<p>'.e($notes).'</p>');
    }
}
