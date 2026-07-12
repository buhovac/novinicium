<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'body', 'live_url', 'sort_order',
    ];

    /** @return HasMany<ProjectImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(ProjectImage::class)->orderBy('order');
    }
}
