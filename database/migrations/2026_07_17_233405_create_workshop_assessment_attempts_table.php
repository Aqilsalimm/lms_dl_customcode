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
        Schema::create('workshop_assessment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('workshop_assessments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            $table->enum('status', ['in_progress', 'completed'])->default('in_progress');
            $table->decimal('total_score', 5, 2)->nullable(); // Total nilai akhir
            $table->boolean('is_passed')->default(false); // Apakah melampaui passing_score?
            
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
            
            // Mencegah user mengerjakan pre-test yang sama berkali-kali
            $table->unique(['assessment_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workshop_assessment_attempts');
    }
};
