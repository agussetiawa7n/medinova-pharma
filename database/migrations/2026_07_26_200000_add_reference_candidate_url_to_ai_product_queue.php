<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The real product photo the search found but this server cannot download.
     *
     * IndiaMart's CDN answers the Hostinger datacenter with HTTP 444 (measured:
     * 200 from a home/office IP, 444 from any datacenter), so the box photo the
     * SerpAPI search located can never be fetched server-side. The URL is stored
     * here so the admin's browser — which is NOT blocked, and gets an
     * Access-Control-Allow-Origin:* response — can fetch it from their own IP
     * and upload the bytes. This column is the hand-off between the two.
     */
    public function up(): void
    {
        Schema::table('ai_product_queue', function (Blueprint $table) {
            $table->string('reference_candidate_url', 1024)->nullable()->after('reference_url');
        });
    }

    public function down(): void
    {
        Schema::table('ai_product_queue', function (Blueprint $table) {
            $table->dropColumn('reference_candidate_url');
        });
    }
};
