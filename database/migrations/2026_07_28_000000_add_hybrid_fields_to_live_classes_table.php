<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('live_classes')) {
            Schema::create('live_classes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->nullable()->constrained('courses')->onDelete('cascade');
                $table->string('title');
                $table->enum('delivery_mode', ['online', 'offline'])->default('online');
                $table->string('meeting_link')->nullable();
                $table->text('location_venue')->nullable();
                $table->string('recording_url')->nullable();
                $table->json('documentation_urls')->nullable();
                $table->dateTime('start_time')->nullable();
                $table->dateTime('end_time')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('live_classes', function (Blueprint $table) {
                if (!Schema::hasColumn('live_classes', 'delivery_mode')) {
                    $table->enum('delivery_mode', ['online', 'offline'])
                          ->default('online')
                          ->after('title');
                }

                if (Schema::hasColumn('live_classes', 'meeting_link')) {
                    $table->string('meeting_link')->nullable()->change();
                } else {
                    $table->string('meeting_link')->nullable()->after('delivery_mode');
                }

                if (!Schema::hasColumn('live_classes', 'location_venue')) {
                    $table->text('location_venue')->nullable()->after('meeting_link');
                }

                if (!Schema::hasColumn('live_classes', 'recording_url')) {
                    $table->string('recording_url')->nullable()->after('location_venue');
                }

                if (!Schema::hasColumn('live_classes', 'documentation_urls')) {
                    $table->json('documentation_urls')->nullable()->after('recording_url');
                }
            });
        }

        // Add hybrid fields to courses table as fallback
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'delivery_mode')) {
                $table->enum('delivery_mode', ['online', 'offline'])->default('online')->after('course_type');
            }
            if (!Schema::hasColumn('courses', 'location_venue')) {
                $table->text('location_venue')->nullable()->after('meeting_url');
            }
            if (!Schema::hasColumn('courses', 'documentation_urls')) {
                $table->json('documentation_urls')->nullable()->after('recording_url');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('live_classes')) {
            Schema::table('live_classes', function (Blueprint $table) {
                $cols = array_filter(['delivery_mode', 'location_venue', 'recording_url', 'documentation_urls'], function($col) {
                    return Schema::hasColumn('live_classes', $col);
                });
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        Schema::table('courses', function (Blueprint $table) {
            $cols = array_filter(['delivery_mode', 'location_venue', 'documentation_urls'], function($col) {
                return Schema::hasColumn('courses', $col);
            });
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
