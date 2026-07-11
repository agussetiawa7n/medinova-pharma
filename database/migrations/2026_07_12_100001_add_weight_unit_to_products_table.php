<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // weight is stored in grams by default; this makes the unit explicit
            // so syrups (ml) / heavier packs (kg) can display correctly too.
            $table->string('weight_unit', 10)->default('g')->after('weight');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('weight_unit');
        });
    }
};
