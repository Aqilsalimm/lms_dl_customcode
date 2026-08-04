<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            $table->string('purpose', 32)->nullable()->after('otp_code');
            $table->index(
                ['user_id', 'purpose', 'used', 'expires_at'],
                'otps_user_purpose_active_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            $table->dropIndex('otps_user_purpose_active_index');
            $table->dropColumn('purpose');
        });
    }
};