<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shared hosting has no queue worker, so AI generation is driven by the
     * admin page polling one step at a time. Overlapping polls used to pick up
     * the same row and generate it several times over. `locked_at` gives us an
     * atomic, self-expiring claim so exactly one request owns an item.
     */
    public function up(): void
    {
        Schema::table('ai_product_queue', function (Blueprint $table) {
            $table->timestamp('locked_at')->nullable()->after('retry_count');
            $table->index(['type', 'status'], 'ai_queue_type_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ai_product_queue', function (Blueprint $table) {
            $table->dropIndex('ai_queue_type_status_idx');
            $table->dropColumn('locked_at');
        });
    }
};
