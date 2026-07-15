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
        Schema::create('ebook_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('ebook_id')->constrained('ebooks')->onDelete('cascade');
            $table->decimal('amount', 12, 2)->default(0.00);
            $table->string('payment_status')->default('pending'); // pending, success, failed, expired
            $table->string('payment_method')->nullable();
            $table->string('snap_token')->nullable();
            $table->timestamp('date')->nullable(); // Date of successful transaction/payment
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ebook_transactions');
    }
};
