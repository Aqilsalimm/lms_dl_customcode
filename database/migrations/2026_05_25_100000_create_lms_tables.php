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
        // 1. Categories Table
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Tags Table
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // 3. Courses Table
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('about')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('bg_color')->nullable();
            $table->string('icon_type')->default('code');
            $table->decimal('price', 15, 2)->default(0.00);
            $table->string('level')->default('Umum'); // SD, SMP, SMA, Umum
            $table->integer('capacity')->default(20);
            $table->string('status')->default('draft'); // draft, published
            $table->timestamps();
        });

        // 4. Course Tag Pivot Table
        Schema::create('course_tag', function (Blueprint $table) {
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('tags')->onDelete('cascade');
            $table->primary(['course_id', 'tag_id']);
        });

        // 5. Modules Table
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->string('title');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 6. Lessons Table
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->onDelete('cascade');
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('video_url')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 7. Quizzes Table
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('time_limit_minutes')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 8. Quiz Questions Table
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->onDelete('cascade');
            $table->text('question_text');
            $table->json('options'); // Options array
            $table->integer('correct_option_index'); // Index in the options array
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 9. Quiz Attempts Table
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('quiz_id')->constrained('quizzes')->onDelete('cascade');
            $table->decimal('score', 5, 2)->default(0.00);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // 10. Bundles Table
        Schema::create('bundles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2)->default(0.00);
            $table->string('thumbnail')->nullable();
            $table->string('status')->default('draft'); // draft, published
            $table->timestamps();
        });

        // 11. Bundle Course Pivot Table
        Schema::create('bundle_course', function (Blueprint $table) {
            $table->foreignId('bundle_id')->constrained('bundles')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->primary(['bundle_id', 'course_id']);
        });

        // 12. Orders / Transactions Table
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('buyable_type'); // App\Models\Course or App\Models\Bundle
            $table->unsignedBigInteger('buyable_id');
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('pending'); // pending, completed, failed, expired
            $table->string('transaction_id')->unique()->nullable(); // midtrans order ID
            $table->string('snap_token')->nullable(); // midtrans snap token
            $table->string('payment_type')->nullable();
            $table->timestamps();
        });

        // 13. Enrollments Table
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->nullable()->constrained('courses')->onDelete('cascade');
            $table->foreignId('bundle_id')->nullable()->constrained('bundles')->onDelete('cascade');
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('bundle_course');
        Schema::dropIfExists('bundles');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('modules');
        Schema::dropIfExists('course_tag');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('categories');
    }
};
