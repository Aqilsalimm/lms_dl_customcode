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
        Schema::table('live_classes', function (Blueprint $table) {
            if (!Schema::hasColumn('live_classes', 'mode')) {
                $table->enum('mode', ['online', 'offline', 'hybrid'])->default('online');
            }
            if (!Schema::hasColumn('live_classes', 'offline_capacity')) {
                $table->unsignedInteger('offline_capacity')->nullable()->comment('Batas kursi fisik');
            }
            if (!Schema::hasColumn('live_classes', 'venue_name')) {
                $table->string('venue_name')->nullable();
            }
            if (!Schema::hasColumn('live_classes', 'venue_address')) {
                $table->text('venue_address')->nullable();
            }
            if (!Schema::hasColumn('live_classes', 'gmaps_url')) {
                $table->string('gmaps_url')->nullable();
            }
            if (!Schema::hasColumn('live_classes', 'gmaps_embed_url')) {
                $table->string('gmaps_embed_url')->nullable();
            }
            if (!Schema::hasColumn('live_classes', 'is_published')) {
                $table->boolean('is_published')->default(true);
            }
            
            // Add composite index for quick lookup
            $table->index(['mode', 'is_published']);
        });

        if (!Schema::hasTable('class_enrollments')) {
            Schema::create('class_enrollments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('live_class_id')->constrained('live_classes')->cascadeOnDelete();
                $table->enum('attendance_type', ['onsite', 'online'])->default('online');
                $table->string('checkin_qr_code')->nullable()->comment('Token QR untuk presensi fisik');
                $table->timestamps();

                // Composite Index O(log N) for validation & IDOR checks
                $table->index(['live_class_id', 'attendance_type', 'user_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('class_enrollments')) {
            Schema::dropIfExists('class_enrollments');
        }

        Schema::table('live_classes', function (Blueprint $table) {
            $table->dropIndex(['mode', 'is_published']);
            
            $cols = ['mode', 'offline_capacity', 'venue_name', 'venue_address', 'gmaps_url', 'gmaps_embed_url', 'is_published'];
            $drop = array_filter($cols, function($col) {
                return Schema::hasColumn('live_classes', $col);
            });
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
