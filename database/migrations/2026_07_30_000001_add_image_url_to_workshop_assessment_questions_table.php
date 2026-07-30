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
        if (Schema::hasTable('workshop_assessment_questions')) {
            Schema::table('workshop_assessment_questions', function (Blueprint $table) {
                if (!Schema::hasColumn('workshop_assessment_questions', 'image_url')) {
                    $table->longText('image_url')->nullable()->after('question_text');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('workshop_assessment_questions')) {
            Schema::table('workshop_assessment_questions', function (Blueprint $table) {
                if (Schema::hasColumn('workshop_assessment_questions', 'image_url')) {
                    $table->dropColumn('image_url');
                }
            });
        }
    }
};
