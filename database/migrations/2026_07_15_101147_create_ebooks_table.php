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
        Schema::create('ebooks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('author');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->decimal('price', 12, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->string('image_cover')->nullable();
            $table->text('url_files')->nullable(); // For large files externally hosted
            $table->string('path_files')->nullable(); // For small files on local shared hosting/vps
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ebooks');
    }
};
