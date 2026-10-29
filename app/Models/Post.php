<?php

namespace App\Models;

use App\Filament\Resources\Posts\Blocks\PostContentBlocks;
use Database\Factories\PostFactory;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $body
 * @property array<string, mixed>|null $body_json
 * @property Carbon|null $published_at
 * @property-read string|null $image_url
 */
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'body',
        'body_json',
        'image_path',
        'author',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'body_json' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Whenever the TipTap document changes, mirror it into `body` as a
     * sanitized HTML snapshot (rollback safety, RSS/excerpt reuse).
     */
    protected static function booted(): void
    {
        static::saving(function (Post $post): void {
            if ($post->isDirty('body_json') && $post->body_json !== null) {
                $post->body = RichContentRenderer::make(PostContentBlocks::withoutUnusableBlocks($post->body_json))
                    ->customBlocks(PostContentBlocks::all())
                    ->toHtml();
            }
        });
    }

    /**
     * Only posts with a publish date in the past are public.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
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
        return static::storageUrlFor($this->image_path);
    }

    /**
     * Turn a stored image path into a public URL. Absolute URLs and
     * root-relative paths pass through; bare paths live on the public disk.
     */
    public static function storageUrlFor(?string $path): ?string
    {
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
