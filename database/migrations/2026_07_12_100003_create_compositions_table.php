<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compositions', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // e.g. "Tadalafil"
            $table->string('slug')->unique();       // tadalafil
            $table->text('overview')->nullable();
            $table->text('how_it_works')->nullable();
            $table->text('uses')->nullable();
            $table->text('side_effects')->nullable();
            $table->text('precautions')->nullable();
            $table->text('medical_disclaimer')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            // needs_review until AI content is generated & (optionally) approved
            $table->string('content_status')->default('needs_review');
            $table->timestamp('content_reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('composition_id')->nullable()->after('category_id')
                  ->constrained('compositions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('composition_id');
        });
        Schema::dropIfExists('compositions');
    }
};
