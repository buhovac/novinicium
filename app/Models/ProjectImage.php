<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectImage extends Model
{
    protected $fillable = ['project_id', 'image_path', 'alt_text', 'order'];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
    protected function imageUrl(): Attribute
    {
        return Attribute::get(function () {
            $path = $this->image_path;
            if (! $path) return null;
            if (str_starts_with($path, '/') || str_starts_with($path, 'http')) {
                return $path;
            }
            if (str_starts_with($path, 'images/')) {
                return '/storage/'.$path;
            }
            return '/storage/images/'.$path;
        });
    }
}
