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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->enum('type', ['session', 'multi_session', 'course_completion'])->default('session');
            $table->json('module_ids')->nullable(); // array of module IDs required for unlocking
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('certificate_id')->constrained()->onDelete('cascade');
            $table->string('certificate_code')->unique();
            $table->timestamp('claimed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'certificate_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_certificates');
        Schema::dropIfExists('certificates');
    }
};
