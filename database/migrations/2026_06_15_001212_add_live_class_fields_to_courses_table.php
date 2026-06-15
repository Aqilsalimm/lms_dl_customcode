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
            $table->string('course_type')->default('async')->after('id');
            $table->dateTime('start_date')->nullable()->after('course_type');
            $table->dateTime('end_date')->nullable()->after('start_date');
            $table->string('timezone')->nullable()->after('end_date');
            $table->string('meeting_url')->nullable()->after('timezone');
            $table->integer('max_participants')->nullable()->after('meeting_url');
            $table->string('recording_url')->nullable()->after('max_participants');
            $table->boolean('is_event_finished')->default(false)->after('recording_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'course_type',
                'start_date',
                'end_date',
                'timezone',
                'meeting_url',
                'max_participants',
                'recording_url',
                'is_event_finished'
            ]);
        });
    }
};
