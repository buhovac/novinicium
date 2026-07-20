<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Key-value store for editable site copy (hero, services, CTA, footer).
 *
 * Values are JSON-cast, so both plain strings and arrays (e.g. the
 * services repeater) round-trip transparently. All reads go through a
 * forever-cache that is invalidated on every write.
 */
class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    /**
     * Generic fallback copy. Lives in the (public) repository, so it must
     * never contain real business content — set the real copy through the
     * admin panel; it is stored in the database only.
     *
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'head_title' => 'Example Studio — software & web development',
            'hero_kicker' => 'Web development studio',
            'hero_title' => 'Your headline goes here.',
            'hero_subtitle' => 'A short paragraph describing what the studio does and for whom. Edit this text in the admin panel under Site Settings.',
            'hero_primary_label' => 'View our work',
            'hero_secondary_label' => 'Get in touch',
            'services_title' => 'What we do',
            'services' => [
                ['title' => 'Service one', 'body' => 'Describe the first service in one or two sentences.'],
                ['title' => 'Service two', 'body' => 'Describe the second service in one or two sentences.'],
                ['title' => 'Service three', 'body' => 'Describe the third service in one or two sentences.'],
            ],
            'cta_title' => 'Ready to start your project?',
            'cta_subtitle' => 'Tell us what you are trying to solve.',
            'cta_button_label' => 'Contact us',
            'footer_title' => 'Example Studio',
            'footer_text' => 'A short footer line about the studio.',
        ];
    }

    /** All stored values, cached forever until the next write.
     * @return array<string, mixed>
     */
    public static function stored(): array
    {
        return Cache::rememberForever('site_settings', function (): array {
            return static::query()->pluck('value', 'key')->all();
        });
    }

    /** Defaults merged with stored overrides — what the frontend receives.
     * @return array<string, mixed>
     */
    public static function shared(): array
    {
        return array_merge(static::defaults(), static::stored());
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('site_settings');
    }
}
