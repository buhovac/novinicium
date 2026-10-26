<?php

namespace App\Filament\Resources\Posts\Blocks;

/**
 * Column layout of a statistics group on wide screens (always stacked on
 * narrow screens).
 */
enum StatisticsLayout: string
{
    case Single = 'single';
    case TwoColumns = 'two';
    case ThreeColumns = 'three';

    public function label(): string
    {
        return match ($this) {
            self::Single => 'Single column',
            self::TwoColumns => 'Two columns',
            self::ThreeColumns => 'Three columns',
        };
    }

    public static function forItemCount(int $count): self
    {
        return match (true) {
            $count >= 3 => self::ThreeColumns,
            $count === 2 => self::TwoColumns,
            default => self::Single,
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
