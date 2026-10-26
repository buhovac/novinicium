<?php

namespace App\Filament\Resources\Posts\Blocks;

/**
 * Callout variants. The label is shown as visible text next to an icon, so
 * the variant never depends on colour alone.
 */
enum CalloutVariant: string
{
    case Information = 'information';
    case Definition = 'definition';
    case Important = 'important';
    case Warning = 'warning';
    case Example = 'example';

    public function label(): string
    {
        return match ($this) {
            self::Information => 'Information',
            self::Definition => 'Definition',
            self::Important => 'Important',
            self::Warning => 'Warning',
            self::Example => 'Example',
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
