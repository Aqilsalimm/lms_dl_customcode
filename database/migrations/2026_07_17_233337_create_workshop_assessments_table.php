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
        Schema::create('workshop_assessments', function (Blueprint $table) {
            $table->id();
            // Relasi ke tabel kelas (induk)
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            
            // Tipe tes: pre_test atau post_test
            $table->enum('type', ['pre_test', 'post_test']);
            
            // Informasi dasar
            $table->string('title');
            $table->text('description')->nullable();
            
            // Pengaturan teknis
            $table->integer('duration_minutes')->nullable(); // Batas waktu pengerjaan
            $table->integer('passing_score')->default(0); // Nilai minimum (KKM) untuk lulus
            $table->boolean('is_published')->default(false); // Status aktif/draft dari builder
            
            // Waktu pengerjaan/akses
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();

            $table->timestamps();
            
            // Memastikan 1 kelas hanya punya 1 pre-test dan 1 post-test
            $table->unique(['course_id', 'type']); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workshop_assessments');
    }
};
