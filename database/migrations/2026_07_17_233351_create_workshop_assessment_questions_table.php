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
        Schema::create('workshop_assessment_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('workshop_assessments')->cascadeOnDelete();
            
            $table->longText('question_text');
            
            // Menyimpan opsi A, B, C, D dalam format array JSON
            $table->json('options'); 
            
            // Menyimpan jawaban benar, misalnya "A" atau value dari JSON
            $table->string('correct_answer'); 
            
            // Bobot nilai jika soal ini dijawab benar (misal: 10 poin)
            $table->integer('points')->default(10); 
            
            // Urutan tampil soal di UI Builder
            $table->integer('order_number')->default(0); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workshop_assessment_questions');
    }
};
