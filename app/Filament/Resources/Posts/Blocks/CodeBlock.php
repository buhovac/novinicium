<?php

namespace App\Filament\Resources\Posts\Blocks;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Str;

/**
 * Code: a plain-text code sample with a language label. Stored and rendered
 * as text only; never highlighted, executed or interpreted as HTML.
 */
class CodeBlock extends PostContentBlock
{
    public const CODE_MAX_LENGTH = 20000;

    public static function getId(): string
    {
        return 'code';
    }

    public static function getLabel(): string
    {
        return 'Code';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalWidth('3xl')
            ->schema([
                Select::make('language')
                    ->options(CodeLanguage::options())
                    ->default(CodeLanguage::PlainText->value)
                    ->required(),
                Textarea::make('code')
                    ->required()
                    ->rows(12)
                    ->maxLength(self::CODE_MAX_LENGTH)
                    ->extraInputAttributes(['class' => 'font-mono', 'spellcheck' => 'false']),
            ]);
    }

    public static function rules(): array
    {
        return [
            'language' => ['required', 'string'],
            'code' => ['required', 'string', 'max:'.self::CODE_MAX_LENGTH],
        ];
    }

    /**
     * Unknown languages become plain text. Indentation is kept; only blank
     * lines around the sample are removed.
     */
    protected static function prepareConfig(array $config): array
    {
        $config['language'] = CodeLanguage::fromLoose($config['language'] ?? null)->value;

        if (is_string($config['code'] ?? null)) {
            $config['code'] = (string) preg_replace('/^(\s*\R)+|(\R\s*)+$/', '', $config['code']);

            if (trim($config['code']) === '') {
                $config['code'] = '';
            }
        }

        return $config;
    }

    protected static function toContentNode(array $config): array
    {
        $language = CodeLanguage::from($config['language']);

        return [
            'language' => $language->value,
            'label' => $language->label(),
            'code' => $config['code'],
        ];
    }

    public static function getPreviewLabel(array $config): string
    {
        $node = static::normalize($config);

        return $node === null ? 'Code (empty)' : "Code — {$node['label']}";
    }

    public static function toPreviewHtml(array $config): ?string
    {
        $node = static::normalize($config);

        if ($node === null) {
            return '<p><em>This code block is empty and will not be shown. Click to edit.</em></p>';
        }

        $lines = preg_split('/\R/', $node['code']) ?: [];
        $excerpt = implode("\n", array_slice($lines, 0, 4)).(count($lines) > 4 ? "\n…" : '');

        return sprintf(
            '<p><strong>%s</strong> · %s</p><pre><code>%s</code></pre>',
            e($node['label']),
            e(Str::plural('line', count($lines), prependCount: true)),
            e($excerpt),
        );
    }

    /**
     * Snapshot fallback for `posts.body` only (rollback/RSS). The public site
     * renders the node through ArticleCode.vue.
     */
    public static function toHtml(array $config, array $data): ?string
    {
        $node = static::normalize($config);

        return $node === null ? null : '<pre><code>'.e($node['code']).'</code></pre>';
    }
}
