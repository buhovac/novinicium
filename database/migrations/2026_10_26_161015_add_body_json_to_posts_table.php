<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive only: body_json holds the TipTap document from the RichEditor.
     * `body` stays NOT NULL and keeps an HTML snapshot, so rolling back the
     * code (or this migration) leaves every post renderable.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->json('body_json')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('body_json');
        });
    }
};
