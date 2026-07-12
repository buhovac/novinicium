<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            // slug = SEO URL + route model binding key. Unique index guarantees
            // no duplicate URLs at the database level, not just app level.
            $table->string('slug')->unique();
            // excerpt kept as its own column (not derived from body) so editors
            // control exactly what the list page shows.
            $table->string('excerpt', 500);
            $table->longText('body');
            $table->string('image_path');
            // author as plain string: posts are not tied to app users (no FK to
            // users on purpose — public content outlives accounts).
            $table->string('author');
            // nullable published_at doubles as a draft flag: NULL = draft.
            // Avoids a separate boolean that can drift out of sync with the date.
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
