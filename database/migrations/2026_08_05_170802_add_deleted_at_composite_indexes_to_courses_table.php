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
        Schema::table('courses', function (Blueprint $table) {
            $indexes = Schema::getIndexes('courses');
            $indexNames = array_column($indexes, 'name');

            if (!in_array('idx_course_inst_del_create', $indexNames)) {
                $table->index(['instructor_id', 'deleted_at', 'created_at'], 'idx_course_inst_del_create');
            }
            if (in_array('idx_course_del_stat', $indexNames)) {
                $table->dropIndex('idx_course_del_stat');
            }
            if (!in_array('idx_course_stat_del_create', $indexNames)) {
                $table->index(['status', 'deleted_at', 'created_at'], 'idx_course_stat_del_create');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $indexes = Schema::getIndexes('courses');
            $indexNames = array_column($indexes, 'name');

            if (in_array('idx_course_inst_del_create', $indexNames)) {
                $table->dropIndex('idx_course_inst_del_create');
            }
            if (in_array('idx_course_stat_del_create', $indexNames)) {
                $table->dropIndex('idx_course_stat_del_create');
            }
        });
    }
};
