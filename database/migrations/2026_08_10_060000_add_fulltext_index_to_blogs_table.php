<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('blogs', function (Blueprint $table) {
            $table->fullText(['title', 'content', 'category', 'tags'], 'idx_blogs_fulltext_search');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('blogs', function (Blueprint $table) {
            $table->dropIndex('idx_blogs_fulltext_search');
        });
    }
};