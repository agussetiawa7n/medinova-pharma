<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The category the admin picked for this batch, before generation ran.
     *
     * Left to itself the model chooses a category per product from the whole
     * list, so a single batch of near-identical items could land in three
     * different places and each one had to be corrected by hand afterwards.
     * Pinning the choice up front makes it deterministic: the id travels with
     * the queue row, is fed to the prompt, and is what the product is saved
     * with. Null keeps the old behaviour — AI decides.
     *
     * No foreign key on purpose: a deleted category must not cascade into
     * deleting queue history, and saveApproved() verifies the id still exists.
     */
    public function up(): void
    {
        Schema::table('ai_product_queue', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->after('type');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('ai_product_queue', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
