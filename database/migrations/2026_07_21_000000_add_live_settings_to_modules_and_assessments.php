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
            $table->string('meeting_url')->nullable()->after('sort_order');
            $table->dateTime('start_date')->nullable()->after('meeting_url');
            $table->dateTime('end_date')->nullable()->after('start_date');
            $table->string('recording_url')->nullable()->after('end_date');
            $table->string('material_file_path')->nullable()->after('recording_url');
        });

        Schema::table('workshop_assessments', function (Blueprint $table) {
            $table->foreignId('module_id')->nullable()->after('course_id')->constrained('modules')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workshop_assessments', function (Blueprint $table) {
            $table->dropForeign(['module_id']);
            $table->dropColumn('module_id');
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn([
                'meeting_url',
                'start_date',
                'end_date',
                'recording_url',
                'material_file_path',
            ]);
        });
    }
};
