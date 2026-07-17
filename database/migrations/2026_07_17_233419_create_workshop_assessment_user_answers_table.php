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
        Schema::create('workshop_assessment_user_answers', function (Blueprint $table) {
            $table->id();
            // Relasi ke sesi pengerjaan user
            $table->foreignId('attempt_id')->constrained('workshop_assessment_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('workshop_assessment_questions')->cascadeOnDelete();
            
            // Jawaban yang dipilih oleh user
            $table->string('selected_answer');
            
            // Apakah jawabannya benar?
            $table->boolean('is_correct')->default(false); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workshop_assessment_user_answers');
    }
};
