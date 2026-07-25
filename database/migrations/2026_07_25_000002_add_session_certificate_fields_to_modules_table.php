<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->boolean('has_session_certificate')->default(false);
            $table->string('certificate_bg_path')->nullable();
            $table->integer('text_name_y_position')->default(44);
            $table->integer('text_title_y_position')->default(56);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn([
                'has_session_certificate',
                'certificate_bg_path',
                'text_name_y_position',
                'text_title_y_position',
            ]);
        });
    }
};
