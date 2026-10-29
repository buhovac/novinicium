<?php

namespace App\Filament\Resources\Posts\Blocks;

/**
 * Display width of an image block. Only the image widens; captions stay in
 * the reading column.
 */
enum ImageWidth: string
{
    case Standard = 'standard';
    case Wide = 'wide';
    case Full = 'full';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard (text width)',
            self::Wide => 'Wide',
            self::Full => 'Full width',
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
