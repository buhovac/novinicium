<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1-N: a project has many screenshots. Separate table (vs JSON column)
        // because each image carries its own data (alt_text, order) and we get
        // real referential integrity + cascade delete.
        Schema::create('project_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            // alt_text is NOT nullable: accessibility is enforced at the schema
            // level — an image cannot enter the gallery without a description.
            $table->string('alt_text', 300);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_images');
    }
};
