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
            $table->integer('access_duration_months')->nullable()->after('price');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->string('status')->default('active')->after('bundle_id');
            $table->timestamp('expires_at')->nullable()->after('enrolled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('access_duration_months');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn(['status', 'expires_at']);
        });
    }
};
