<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An admin-supplied photo of the actual product.
     *
     * Image search is a best-effort lookup against a third party: when it finds
     * nothing the pipeline falls back to having the model invent packaging,
     * which is not the product. This gives the admin a deterministic way to
     * point at the real box — paste the URL and the search step is skipped.
     */
    public function up(): void
    {
        Schema::table('ai_product_queue', function (Blueprint $table) {
            $table->string('reference_url', 1024)->nullable()->after('image_raw_url');
        });
    }

    public function down(): void
    {
        Schema::table('ai_product_queue', function (Blueprint $table) {
            $table->dropColumn('reference_url');
        });
    }
};
