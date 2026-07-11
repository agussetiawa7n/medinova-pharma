<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('faq')->nullable()->after('composition');            // [{q, a}, ...]
            $table->text('medical_disclaimer')->nullable()->after('faq');
            $table->text('how_it_works')->nullable()->after('medical_disclaimer');
            $table->text('side_effects')->nullable()->after('how_it_works');
            $table->text('contraindications')->nullable()->after('side_effects');
            $table->string('drug_class')->nullable()->after('contraindications');
            // published = live; needs_review = failed AI validation, hidden until an admin approves
            $table->string('content_status')->default('published')->after('drug_class');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'faq', 'medical_disclaimer', 'how_it_works',
                'side_effects', 'contraindications', 'drug_class', 'content_status',
            ]);
        });
    }
};
