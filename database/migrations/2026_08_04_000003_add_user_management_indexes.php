<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'status', 'created_at'], 'users_role_status_created_index');
            $table->index(['deleted_at', 'created_at'], 'users_deleted_created_index');
        });

    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_status_created_index');
            $table->dropIndex('users_deleted_created_index');
        });
    }
};