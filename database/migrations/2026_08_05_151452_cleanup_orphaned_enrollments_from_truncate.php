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
        // 1. Delete enrollments where user_id no longer exists in users table
        \Illuminate\Support\Facades\DB::table('enrollments')->whereNotExists(function ($query) {
            $query->select(\Illuminate\Support\Facades\DB::raw(1))
                  ->from('users')
                  ->whereColumn('users.id', 'enrollments.user_id');
        })->delete();

        // 2. Delete enrollments where course_id no longer exists in courses table
        \Illuminate\Support\Facades\DB::table('enrollments')->whereNotNull('course_id')->whereNotExists(function ($query) {
            $query->select(\Illuminate\Support\Facades\DB::raw(1))
                  ->from('courses')
                  ->whereColumn('courses.id', 'enrollments.course_id');
        })->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot restore deleted orphaned enrollments
    }
};
