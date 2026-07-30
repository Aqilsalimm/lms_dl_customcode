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
                if (!Schema::hasIndex('workshop_assessment_attempts', 'idx_waa_user_assessment_status')) {
                    $table->index(['user_id', 'assessment_id', 'status'], 'idx_waa_user_assessment_status');
                }
                if (!Schema::hasIndex('workshop_assessment_attempts', 'idx_waa_user_assessment_passed')) {
                    $table->index(['user_id', 'assessment_id', 'is_passed'], 'idx_waa_user_assessment_passed');
                }
            });
        }

        // 2. User Answers
        if (Schema::hasTable('workshop_assessment_user_answers')) {
            Schema::table('workshop_assessment_user_answers', function (Blueprint $table) {
                if (!Schema::hasIndex('workshop_assessment_user_answers', 'idx_waua_attempt_question')) {
                    $table->index(['attempt_id', 'question_id'], 'idx_waua_attempt_question');
                }
            });
        }

        // 3. Learning Logs
        if (Schema::hasTable('student_learning_logs')) {
            Schema::table('student_learning_logs', function (Blueprint $table) {
                if (!Schema::hasIndex('student_learning_logs', 'idx_sll_user_course_lesson')) {
                    $table->index(['user_id', 'course_id', 'lesson_id'], 'idx_sll_user_course_lesson');
                }
            });
        }

        // 4. Workshop Assessments
        if (Schema::hasTable('workshop_assessments')) {
            Schema::table('workshop_assessments', function (Blueprint $table) {
                if (!Schema::hasIndex('workshop_assessments', 'idx_wa_course_type')) {
                    $table->index(['course_id', 'type'], 'idx_wa_course_type');
                }
                if (!Schema::hasIndex('workshop_assessments', 'idx_wa_module_type')) {
                    $table->index(['module_id', 'type'], 'idx_wa_module_type');
                }
            });
        }

        // 5. Courses
        if (Schema::hasTable('courses')) {
            Schema::table('courses', function (Blueprint $table) {
                if (!Schema::hasIndex('courses', 'idx_courses_status_type')) {
                    $table->index(['status', 'course_type'], 'idx_courses_status_type');
                }
            });
        }

        // 6. Modules
        if (Schema::hasTable('modules')) {
            Schema::table('modules', function (Blueprint $table) {
                if (!Schema::hasIndex('modules', 'idx_modules_course_sort')) {
                    $table->index(['course_id', 'sort_order'], 'idx_modules_course_sort');
                }
            });
        }

        // 7. Lessons
        if (Schema::hasTable('lessons')) {
            Schema::table('lessons', function (Blueprint $table) {
                if (!Schema::hasIndex('lessons', 'idx_lessons_module_sort')) {
                    $table->index(['module_id', 'sort_order'], 'idx_lessons_module_sort');
                }
            });
        }

        // 8. Subscriptions
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (!Schema::hasIndex('subscriptions', 'idx_subs_user_course_status')) {
                    $table->index(['user_id', 'course_id', 'status'], 'idx_subs_user_course_status');
                }
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
                if (Schema::hasIndex('workshop_assessment_attempts', 'idx_waa_user_assessment_status')) {
                    $table->dropIndex('idx_waa_user_assessment_status');
                }
                if (Schema::hasIndex('workshop_assessment_attempts', 'idx_waa_user_assessment_passed')) {
                    $table->dropIndex('idx_waa_user_assessment_passed');
                }
            });
        }

        if (Schema::hasTable('workshop_assessment_user_answers')) {
            Schema::table('workshop_assessment_user_answers', function (Blueprint $table) {
                if (Schema::hasIndex('workshop_assessment_user_answers', 'idx_waua_attempt_question')) {
                    $table->dropIndex('idx_waua_attempt_question');
                }
            });
        }

        if (Schema::hasTable('student_learning_logs')) {
            Schema::table('student_learning_logs', function (Blueprint $table) {
                if (Schema::hasIndex('student_learning_logs', 'idx_sll_user_course_lesson')) {
                    $table->dropIndex('idx_sll_user_course_lesson');
                }
            });
        }

        if (Schema::hasTable('workshop_assessments')) {
            Schema::table('workshop_assessments', function (Blueprint $table) {
                if (Schema::hasIndex('workshop_assessments', 'idx_wa_course_type')) {
                    $table->dropIndex('idx_wa_course_type');
                }
                if (Schema::hasIndex('workshop_assessments', 'idx_wa_module_type')) {
                    $table->dropIndex('idx_wa_module_type');
                }
            });
        }

        if (Schema::hasTable('courses')) {
            Schema::table('courses', function (Blueprint $table) {
                if (Schema::hasIndex('courses', 'idx_courses_status_type')) {
                    $table->dropIndex('idx_courses_status_type');
                }
            });
        }

        if (Schema::hasTable('modules')) {
            Schema::table('modules', function (Blueprint $table) {
                if (Schema::hasIndex('modules', 'idx_modules_course_sort')) {
                    $table->dropIndex('idx_modules_course_sort');
                }
            });
        }

        if (Schema::hasTable('lessons')) {
            Schema::table('lessons', function (Blueprint $table) {
                if (Schema::hasIndex('lessons', 'idx_lessons_module_sort')) {
                    $table->dropIndex('idx_lessons_module_sort');
                }
            });
        }

        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (Schema::hasIndex('subscriptions', 'idx_subs_user_course_status')) {
                    $table->dropIndex('idx_subs_user_course_status');
                }
            });
        }
    }
};
