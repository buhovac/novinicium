<?php

namespace App\Filament\Resources\Posts\Blocks;

/**
 * Language label for code blocks. A label only: nothing is highlighted,
 * executed or interpreted.
 */
enum CodeLanguage: string
{
    case PlainText = 'plaintext';
    case Html = 'html';
    case Css = 'css';
    case JavaScript = 'javascript';
    case TypeScript = 'typescript';
    case Vue = 'vue';
    case Php = 'php';
    case Json = 'json';
    case Sql = 'sql';
    case Shell = 'shell';
    case Yaml = 'yaml';
    case Markdown = 'markdown';

    public function label(): string
    {
        return match ($this) {
            self::PlainText => 'Plain text',
            self::Html => 'HTML',
            self::Css => 'CSS',
            self::JavaScript => 'JavaScript',
            self::TypeScript => 'TypeScript',
            self::Vue => 'Vue',
            self::Php => 'PHP',
            self::Json => 'JSON',
            self::Sql => 'SQL',
            self::Shell => 'Shell',
            self::Yaml => 'YAML',
            self::Markdown => 'Markdown',
        };
    }

    /**
     * Map a free-form language name (e.g. from a pasted ```js fence in a
     * native code block) to a known label, or plain text.
     */
    public static function fromLoose(mixed $language): self
    {
        if (! is_string($language)) {
            return self::PlainText;
        }

        $language = strtolower(trim($language));

        return self::tryFrom($language) ?? match ($language) {
            'js' => self::JavaScript,
            'ts' => self::TypeScript,
            'bash', 'sh', 'zsh', 'console' => self::Shell,
            'yml' => self::Yaml,
            'md' => self::Markdown,
            'text', 'txt', 'plain' => self::PlainText,
            default => self::PlainText,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
