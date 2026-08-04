<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep reruns safe when a previous production deploy stopped after
        // adding the column but before Laravel recorded this migration.
        if (! Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (! Schema::hasIndex('users', 'users_role_status_created_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index(['role', 'status', 'created_at'], 'users_role_status_created_index');
            });
        }

        if (! Schema::hasIndex('users', 'users_deleted_created_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index(['deleted_at', 'created_at'], 'users_deleted_created_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('users', 'users_role_status_created_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_role_status_created_index');
            });
        }

        if (Schema::hasIndex('users', 'users_deleted_created_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_deleted_created_index');
            });
        }
    }
};