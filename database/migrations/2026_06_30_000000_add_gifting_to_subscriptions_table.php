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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('is_gifted')->default(false)->after('course_id');
            $table->string('gifted_by')->nullable()->after('is_gifted');
            $table->date('next_billing_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['is_gifted', 'gifted_by']);
            $table->date('next_billing_date')->nullable(false)->change();
        });
    }
};
