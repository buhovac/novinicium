<?php

namespace App\Filament\Resources\Posts\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Callout: a highlighted aside (information, definition, important, warning,
 * example) with a required title and a plain-text body.
 */
class CalloutBlock extends PostContentBlock
{
    public const TITLE_MAX_LENGTH = 120;

    public const BODY_MAX_LENGTH = 1000;

    public static function getId(): string
    {
        return 'callout';
    }

    public static function getLabel(): string
    {
        return 'Callout';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription('Highlighted note. The type is shown as an icon and a text label.')
            ->schema([
                Select::make('variant')
                    ->label('Type')
                    ->options(CalloutVariant::options())
                    ->default(CalloutVariant::Information->value)
                    ->required(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(self::TITLE_MAX_LENGTH),
                Textarea::make('body')
                    ->required()
                    ->rows(5)
                    ->maxLength(self::BODY_MAX_LENGTH)
                    ->helperText('Plain text. Leave an empty line between paragraphs.'),
            ]);
    }

    public static function rules(): array
    {
        return [
            'variant' => ['required', Rule::enum(CalloutVariant::class)],
            'title' => ['required', 'string', 'max:'.self::TITLE_MAX_LENGTH],
            'body' => ['required', 'string', 'max:'.self::BODY_MAX_LENGTH],
        ];
    }

    /**
     * An unknown variant is recoverable: fall back to Information.
     */
    protected static function prepareConfig(array $config): array
    {
        $variant = $config['variant'] ?? null;

        if (! is_string($variant) || CalloutVariant::tryFrom($variant) === null) {
            $config['variant'] = CalloutVariant::Information->value;
        }

        foreach (['title', 'body'] as $field) {
            if (is_string($config[$field] ?? null)) {
                $config[$field] = trim($config[$field]);
            }
        }

        return $config;
    }

    protected static function toContentNode(array $config): array
    {
        $variant = CalloutVariant::from($config['variant']);

        return [
            'variant' => $variant->value,
            'label' => $variant->label(),
            'title' => $config['title'],
            'paragraphs' => self::paragraphs($config['body']),
        ];
    }

    public static function getPreviewLabel(array $config): string
    {
        $node = static::normalize($config);

        return $node === null
            ? 'Callout (incomplete)'
            : "Callout — {$node['label']}: {$node['title']}";
    }

    public static function toPreviewHtml(array $config): ?string
    {
        $node = static::normalize($config);

        if ($node === null) {
            return '<p><em>This callout is incomplete and will not be shown. Click to edit.</em></p>';
        }

        return sprintf(
            '<p><strong>%s:</strong> %s</p><p>%s</p>',
            e($node['label']),
            e($node['title']),
            e(Str::limit(implode(' ', $node['paragraphs']), 160)),
        );
    }

    /**
     * Snapshot fallback for `posts.body` only (rollback/RSS). Not used by the
     * public site, which renders the node through ArticleCallout.vue.
     */
    public static function toHtml(array $config, array $data): ?string
    {
        $node = static::normalize($config);

        if ($node === null) {
            return null;
        }

        $paragraphs = implode('', array_map(
            fn (string $paragraph): string => '<p>'.e($paragraph).'</p>',
            $node['paragraphs'],
        ));

        return sprintf(
            '<aside><p><strong>%s: %s</strong></p>%s</aside>',
            e($node['label']),
            e($node['title']),
            $paragraphs,
        );
    }

    /**
     * @return list<string>
     */
    private static function paragraphs(string $body): array
    {
        return array_values(array_filter(
            array_map(trim(...), preg_split('/\R\s*\R/', $body) ?: []),
            fn (string $paragraph): bool => $paragraph !== '',
        ));
    }
}
