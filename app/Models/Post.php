<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'title', 'slug', 'excerpt', 'body', 'image_path', 'author', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /** Only posts with a publish date in the past are public. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
    /**
     * Normalizira image_path: podnosi i seedane ("/images/x.svg")
     * i Filament uploade (golo ime ili "images/x.jpg").
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(function () {
            $path = $this->image_path;
            if (! $path) return null;
            if (str_starts_with($path, '/') || str_starts_with($path, 'http')) {
                return $path;                          // seedane: /images/...
            }
            if (str_starts_with($path, 'images/')) {
                return '/storage/'.$path;              // Filament: images/x.jpg
            }
            return '/storage/images/'.$path;           // golo ime: x.jpg
        });
    }
}
