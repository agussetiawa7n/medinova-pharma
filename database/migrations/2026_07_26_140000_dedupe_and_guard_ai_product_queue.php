<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One product name should have one live queue row per type.
     *
     * Duplicates were being produced by more than one path (a re-run alongside
     * an already-approved row, a manual regenerate, a cron tick racing the admin
     * page), each costing a second paid generation and showing the product twice
     * with different results. Fixing the callers is necessary but not sufficient
     * — this makes it impossible at the storage layer.
     *
     * Rows already written to the catalogue (`saved`) and rows the admin chose to
     * `skip` are historical records, so they are excluded from the constraint;
     * only live work is unique.
     */
    public function up(): void
    {
        $this->removeExistingDuplicates();

        Schema::table('ai_product_queue', function (Blueprint $table) {
            $table->string('dedupe_key')->nullable()->after('product_name');
            $table->unique('dedupe_key', 'ai_queue_dedupe_unique');
        });

        // Backfill: live rows get a key, terminal rows stay null and therefore
        // fall outside the unique index (MySQL ignores NULLs for uniqueness).
        DB::table('ai_product_queue')
            ->whereNotIn('status', ['saved', 'skipped'])
            ->update([
                'dedupe_key' => DB::raw("CONCAT(`type`, ':', LOWER(TRIM(`product_name`)))"),
            ]);
    }

    private function removeExistingDuplicates(): void
    {
        $groups = DB::table('ai_product_queue')
            ->selectRaw('`type`, LOWER(TRIM(`product_name`)) as norm_name, COUNT(*) as total')
            ->whereNotIn('status', ['saved', 'skipped'])
            ->groupBy('type', 'norm_name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            // Keep the approved row if there is one, otherwise the newest.
            $keepId = DB::table('ai_product_queue')
                ->where('type', $group->type)
                ->whereRaw('LOWER(TRIM(`product_name`)) = ?', [$group->norm_name])
                ->whereNotIn('status', ['saved', 'skipped'])
                ->orderByRaw('CASE WHEN approved_by IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('id')
                ->value('id');

            DB::table('ai_product_queue')
                ->where('type', $group->type)
                ->whereRaw('LOWER(TRIM(`product_name`)) = ?', [$group->norm_name])
                ->whereNotIn('status', ['saved', 'skipped'])
                ->where('id', '!=', $keepId)
                ->delete();
        }
    }

    public function down(): void
    {
        Schema::table('ai_product_queue', function (Blueprint $table) {
            $table->dropUnique('ai_queue_dedupe_unique');
            $table->dropColumn('dedupe_key');
        });
    }
};
