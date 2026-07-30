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
        // 1. Assessment Attempts
        if (Schema::hasTable('workshop_assessment_attempts')) {
            Schema::table('workshop_assessment_attempts', function (Blueprint $table) {
                $table->index(['user_id', 'assessment_id', 'status'], 'idx_waa_user_assessment_status');
                $table->index(['user_id', 'assessment_id', 'is_passed'], 'idx_waa_user_assessment_passed');
            });
        }

        // 2. User Answers
        if (Schema::hasTable('workshop_assessment_user_answers')) {
            Schema::table('workshop_assessment_user_answers', function (Blueprint $table) {
                $table->index(['attempt_id', 'question_id'], 'idx_waua_attempt_question');
            });
        }

        // 3. Learning Logs
        if (Schema::hasTable('student_learning_logs')) {
            Schema::table('student_learning_logs', function (Blueprint $table) {
                $table->index(['user_id', 'course_id', 'lesson_id'], 'idx_sll_user_course_lesson');
            });
        }

        // 4. Workshop Assessments
        if (Schema::hasTable('workshop_assessments')) {
            Schema::table('workshop_assessments', function (Blueprint $table) {
                $table->index(['course_id', 'type'], 'idx_wa_course_type');
                $table->index(['module_id', 'type'], 'idx_wa_module_type');
            });
        }

        // 5. Courses
        if (Schema::hasTable('courses')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->index(['status', 'course_type'], 'idx_courses_status_type');
            });
        }

        // 6. Modules
        if (Schema::hasTable('modules')) {
            Schema::table('modules', function (Blueprint $table) {
                $table->index(['course_id', 'sort_order'], 'idx_modules_course_sort');
            });
        }

        // 7. Lessons
        if (Schema::hasTable('lessons')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->index(['module_id', 'sort_order'], 'idx_lessons_module_sort');
            });
        }

        // 8. Subscriptions
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->index(['user_id', 'course_id', 'status'], 'idx_subs_user_course_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('workshop_assessment_attempts')) {
            Schema::table('workshop_assessment_attempts', function (Blueprint $table) {
                $table->dropIndex('idx_waa_user_assessment_status');
                $table->dropIndex('idx_waa_user_assessment_passed');
            });
        }

        if (Schema::hasTable('workshop_assessment_user_answers')) {
            Schema::table('workshop_assessment_user_answers', function (Blueprint $table) {
                $table->dropIndex('idx_waua_attempt_question');
            });
        }

        if (Schema::hasTable('student_learning_logs')) {
            Schema::table('student_learning_logs', function (Blueprint $table) {
                $table->dropIndex('idx_sll_user_course_lesson');
            });
        }

        if (Schema::hasTable('workshop_assessments')) {
            Schema::table('workshop_assessments', function (Blueprint $table) {
                $table->dropIndex('idx_wa_course_type');
                $table->dropIndex('idx_wa_module_type');
            });
        }

        if (Schema::hasTable('courses')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropIndex('idx_courses_status_type');
            });
        }

        if (Schema::hasTable('modules')) {
            Schema::table('modules', function (Blueprint $table) {
                $table->dropIndex('idx_modules_course_sort');
            });
        }

        if (Schema::hasTable('lessons')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->dropIndex('idx_lessons_module_sort');
            });
        }

        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropIndex('idx_subs_user_course_status');
            });
        }
    }
};
