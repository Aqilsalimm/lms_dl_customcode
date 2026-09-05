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
        Schema::table('user_certificates', function (Blueprint $table) {
            $table->foreignId('source_enrollment_id')
                ->nullable()
                ->after('certificate_id')
                ->constrained('enrollments')
                ->onDelete('restrict');
            $table->json('identity_snapshot')->nullable()->after('claimed_at');
            $table->unsignedSmallInteger('snapshot_version')->default(1)->after('identity_snapshot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_certificates', function (Blueprint $table) {
            $table->dropForeign(['source_enrollment_id']);
            $table->dropColumn(['source_enrollment_id', 'identity_snapshot', 'snapshot_version']);
        });
    }
};
