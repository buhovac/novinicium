<?php

namespace App\Filament\Resources\Posts\Blocks;

/**
 * Chart types the Chart block can draw. Pie and doughnut ("circular") show
 * one series of non-negative values only.
 */
enum ChartType: string
{
    case Bar = 'bar';
    case Line = 'line';
    case Pie = 'pie';
    case Doughnut = 'doughnut';

    public function label(): string
    {
        return match ($this) {
            self::Bar => 'Bar',
            self::Line => 'Line',
            self::Pie => 'Pie',
            self::Doughnut => 'Doughnut',
        };
    }

    public function isCircular(): bool
    {
        return $this === self::Pie || $this === self::Doughnut;
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
