<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_product_queue', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->json('generated_data')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_raw_url')->nullable();
            $table->string('text_model_used')->nullable();
            $table->string('image_model_used')->nullable();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_product_queue');
    }
};
