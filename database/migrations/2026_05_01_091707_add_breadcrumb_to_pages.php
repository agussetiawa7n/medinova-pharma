<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('breadcrumb_title')->nullable()->after('content');
            $table->string('breadcrumb_image')->nullable()->after('breadcrumb_title');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['breadcrumb_title', 'breadcrumb_image']);
        });
    }
};
