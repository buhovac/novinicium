<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string|null $image_url
 */
class ProjectImage extends Model
{
    protected $fillable = [
        'project_id',
        'image_path',
        'alt_text',
        'order',
    ];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return Attribute<string|null, never> */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->resolveImageUrl(),
        );
    }

    private function resolveImageUrl(): ?string
    {
        $path = $this->image_path;

        if (! $path) {
            return null;
        }

        if (
            str_starts_with($path, '/')
            || str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
        ) {
            return $path;
        }

        if (str_starts_with($path, 'images/')) {
            return '/storage/'.$path;
        }

        return '/storage/images/'.$path;
    }
}
